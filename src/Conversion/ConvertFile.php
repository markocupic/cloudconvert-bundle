<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Conversion;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\User;
use Markocupic\CloudconvertBundle\Exception\ConversionFailedException;
use Markocupic\CloudconvertBundle\Exception\CreateFileFromStreamException;
use Markocupic\CloudconvertBundle\Exception\InvalidTargetDirectoryException;
use Markocupic\CloudconvertBundle\Exception\SourceNotFoundException;
use Markocupic\CloudconvertBundle\Logger\ContaoLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\UnicodeString;

final class ConvertFile
{
    private const CONVERSION_JOB_NAME = 'conversionJob';
    private const IMPORT_FILE_TASK_NAME = 'importFileTask';
    private const CONVERSION_TASK_NAME = 'conversionTask';
    private const EXPORT_TASK_NAME = 'exportFileTask';

    private string $apiKey;
    private string $sandboxApiKey;
    private string|null $source = null;
    private string|null $format = null;
    private bool $sendToBrowser = false;
    private bool $sendToBrowserInline = false;
    private bool $uncached = true;
    private bool $sandbox = false;
    private bool $deleteFileAfterSend = false;
    private string|null $targetPath = null;
    private string|null $cacheHashCode = null;
    private array $options = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoLogger $contaoLogger,
        private readonly string $cloudConvertCacheDir,
        string $cloudConvertApiKey,
        string $cloudConvertSandboxApiKey = '',
        private readonly Security|null $security = null,
    ) {
        $this->apiKey = $cloudConvertApiKey;
        $this->sandboxApiKey = $cloudConvertSandboxApiKey;
    }

    public function reset(): self
    {
        $this->source = null;
        $this->format = null;
        $this->sendToBrowser = false;
        $this->sendToBrowserInline = false;
        $this->uncached = true;
        $this->sandbox = false;
        $this->deleteFileAfterSend = false;
        $this->targetPath = null;
        $this->cacheHashCode = null;
        $this->clearOptions();

        return $this;
    }

    public function file(string $source = null): self
    {
        if (!is_file($source)) {
            throw new SourceNotFoundException('Could not find source file at "'.$source.'".');
        }

        $this->source = $source;

        return $this;
    }

    public function getSource(): string|null
    {
        return $this->source;
    }

    public function sendToBrowser(bool $sendToBrowser = false, bool $inline = false, bool $deleteFileAfterSend = false): self
    {
        $this->sendToBrowser = $sendToBrowser;
        $this->sendToBrowserInline = $inline;
        $this->deleteFileAfterSend = $deleteFileAfterSend;

        return $this;
    }

    public function uncached(bool $uncached = false): self
    {
        $this->uncached = $uncached;

        return $this;
    }

    public function sandbox(bool $sandbox = false): self
    {
        $this->sandbox = $sandbox;

        return $this;
    }

    public function convertTo(string $format, string $targetPath = null): string
    {
        if (!is_file($this->source)) {
            throw new SourceNotFoundException('Could not find source file at "'.$this->source.'".');
        }

        $this->format = strtolower(ltrim($format, '.'));

        if ($targetPath) {
            $this->setTargetPath($targetPath);
        } else {
            // Write the converted file to the source directory,
            // if the target path has not been set.
            if (null === $this->getTargetPath()) {
                $targetPath = sprintf(
                    '%s/%s.%s',
                    \dirname($this->source),
                    pathinfo($this->source, PATHINFO_FILENAME),
                    $this->format,
                );
                $this->setTargetPath($targetPath);
            }
        }

        // Try to get the file from cache by hashcode
        $fromCache = $this->getCachedFromHashCode($this->cacheHashCode, $format, $this->cloudConvertCacheDir);

        if (null !== $fromCache) {
            $file = $fromCache;
            $fs = new Filesystem();

            // Copy resource from cache to target path
            $fs->copy($file->getRealPath(), $this->getTargetPath(), true);
        } elseif (is_file($this->getTargetPath()) && !$this->uncached && empty($this->cacheHashCode)) {
            // Load resource from target path if cache is enabled and target path is a file resource.
            $file = new \SplFileObject($this->getTargetPath());
        } else {
            // Convert file to the target format if it can not be found in the cache.
            $file = $this->convert();
        }

        // Store resource
        if (null === $fromCache && !empty($this->cacheHashCode) && !$this->uncached) {
            $this->addToCacheFromHashCode($this->cacheHashCode, $format, $this->cloudConvertCacheDir, $file);
        }

        if ($this->sendToBrowser) {
            $this->sendFileToBrowser($file->getRealPath(), '', $this->sendToBrowserInline, $this->deleteFileAfterSend);
        }

        $this->reset();

        return $file->getRealPath();
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOption(string $key, $varValue): self
    {
        $this->options[$key] = $varValue;

        return $this;
    }

    public function removeOption(string $key): self
    {
        if (isset($this->options[$key])) {
            unset($this->options[$key]);
        }

        return $this;
    }

    public function clearOptions(): self
    {
        $this->options = [];

        return $this;
    }

    public function getCacheHashCode(): string|null
    {
        return $this->cacheHashCode;
    }

    public function setCacheHashCode(string|null $hashCode): self
    {
        $this->cacheHashCode = $hashCode;

        return $this;
    }

    private function getCachedFromHashCode($cacheHashCode, $format, $cloudConvertCacheDir): \SplFileObject|null
    {
        if (empty($cacheHashCode)) {
            return null;
        }

        if ($this->uncached) {
            return null;
        }

        $path = $cloudConvertCacheDir.'/'.$format.'/'.$cacheHashCode;
        $path = Path::canonicalize($path);

        if (false === is_file($path)) {
            return null;
        }

        $splFile = new \SplFileObject($path);

        if (false === $splFile->isReadable()) {
            return null;
        }

        if (false === $splFile->getSize()) {
            return null;
        }

        if (0 === $splFile->getSize()) {
            return null;
        }

        return $splFile;
    }

    private function addToCacheFromHashCode(string $cacheHashCode, string $format, string $cloudConvertCacheDir, \SplFileObject $resource): void
    {
        $targetPath = Path::canonicalize($cloudConvertCacheDir.'/'.$format.'/'.$cacheHashCode);

        $fs = new Filesystem();
        $fs->mkdir(\dirname($targetPath));
        $fs->copy($resource->getRealPath(), $targetPath, true);
    }

    private function convert(): \SplFileObject
    {
        $cloudConvert = new CloudConvert([
            'api_key' => $this->sandbox ? $this->sandboxApiKey : $this->apiKey,
            'sandbox' => $this->sandbox,
        ]);

        $job = (new Job())
            ->setTag(self::CONVERSION_JOB_NAME)
            ->addTask(
                $this->getImportTask()
            )
            ->addTask(
                $this->getConversionTask()
            )
            ->addTask(
                $this->getExportTask()
            )
        ;

        $cloudConvert->jobs()->create($job);

        // Get upload task
        $uploadTask = $job->getTasks()
            ->whereName(self::IMPORT_FILE_TASK_NAME)[0];

        // Upload file to the CloudConvert server
        $cloudConvert->tasks()
            ->upload($uploadTask, fopen($this->source, 'r'), basename($this->source))
        ;

        // Wait for job completion
        $cloudConvert->jobs()->wait($job);

        $file = $job->getExportUrls();

        if (!\is_array($file) || null === $file[0]) {
            throw new ConversionFailedException(sprintf('CloudConvert file conversion for "%s" failed.', $this->source));
        }

        $source = $cloudConvert
            ->getHttpTransport()
            ->download($file[0]->url)
            ->detach()
        ;

        // Delete old file
        if (file_exists($this->getTargetPath())) {
            unlink($this->getTargetPath());
        }

        // Save file to the target directory
        $dest = fopen($this->getTargetPath(), 'w');
        $bytesCopied = stream_copy_to_stream($source, $dest);
        fclose($dest);

        if (false === $bytesCopied || 0 === $bytesCopied) {
            throw new CreateFileFromStreamException(sprintf('Could not create file "%s" from stream.', $this->getTargetPath()));
        }

        // Contao log
        $username = 'ANONYMOUS';

        if ($this->security) {
            $user = $this->security->getUser();

            if ($user instanceof User) {
                $username = $user->getUserIdentifier();
            }
        }

        $scope = 'TEST';
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            if ($request->attributes->has('_scope')) {
                $scope = strtoupper($request->attributes->get('_scope'));
            }
        }

        $this->contaoLogger->log(
            sprintf(
                'User "%s" (Scope: %s) successfully converted "%s" to "%s" using the CloudConvert API.',
                $username,
                $scope,
                basename($this->source),
                basename($this->getTargetPath()),
            ),
            __METHOD__,
        );

        return new \SplFileObject($this->getTargetPath());
    }

    private function getTargetPath(): string|null
    {
        return $this->targetPath;
    }

    private function getImportTask(): Task
    {
        return new Task('import/upload', self::IMPORT_FILE_TASK_NAME);
    }

    private function getConversionTask(): Task
    {
        $task = (new Task('convert', self::CONVERSION_TASK_NAME))
            ->set('input', self::IMPORT_FILE_TASK_NAME)
            ->set('output_format', $this->format)
        ;

        $options = $this->getOptions();

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $task->set($key, $value);
            }
        }

        return $task;
    }

    private function getExportTask(): Task
    {
        return (new Task('export/url', self::EXPORT_TASK_NAME))
            ->set('input', self::CONVERSION_TASK_NAME)
        ;
    }

    private function setTargetPath(string $targetPath): void
    {
        $fs = new Filesystem();

        // Create the folder if it doesn't exist
        $fs->mkdir(\dirname($targetPath));

        if (!is_dir(\dirname($targetPath)) || !is_writable(\dirname($targetPath))) {
            throw new InvalidTargetDirectoryException(sprintf('Could not create the target directory. Or the target directory is not writable at "%s"', \dirname($targetPath)));
        }

        $this->targetPath = $targetPath;
    }

    private function sendFileToBrowser(string $filePath, string $filename = '', bool $inline = false, bool $deleteFileAfterSend = false): void
    {
        $response = new BinaryFileResponse($filePath);
        $response->setPrivate(); // public by default
        $response->setAutoEtag();

        $response->setContentDisposition(
            $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            (new UnicodeString(basename($filePath)))->ascii()->toString()
        );

        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($filePath);

        $response->headers->addCacheControlDirective('must-revalidate');
        $response->headers->set('Connection', 'close');
        $response->headers->set('Content-Type', $mimeType);
        $response->deleteFileAfterSend($deleteFileAfterSend);

        throw new ResponseException($response);
    }
}
