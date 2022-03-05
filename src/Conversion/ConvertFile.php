<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
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
use Markocupic\CloudconvertBundle\Logger\ContaoLogger;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\UnicodeString;

class ConvertFile
{
    private const CONVERSION_JOB_NAME = 'conversionJob';
    private const IMPORT_FILE_TASK_NAME = 'importFileTask';
    private const CONVERSION_TASK_NAME = 'conversionTask';
    private const EXPORT_TASK_NAME = 'exportFileTask';

    private ContaoLogger $contaoLogger;
    private string $apiKey;
    private string $sandboxApiKey;
    private ?string $source = null;
    private ?string $format = null;
    private bool $sendToBrowser = false;
    private bool $sendToBrowserInline = false;
    private bool $uncached = true;
    private bool $sandbox = false;
    private ?string $targetPath = null;
    private array $options = [];

    public function __construct(ContaoLogger $contaoLogger, string $cloudconvertApiKey, string $cloudconvertSandboxApiKey = '')
    {
        $this->contaoLogger = $contaoLogger;
        $this->apiKey = $cloudconvertApiKey;
        $this->sandboxApiKey = $cloudconvertSandboxApiKey;
    }

    public function reset(): self
    {
        $this->source = null;
        $this->format = null;
        $this->sendToBrowser = false;
        $this->sendToBrowserInline = false;
        $this->uncached = true;
        $this->sandbox = false;
        $this->targetPath = null;
        $this->clearOptions();

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function file(string $source): self
    {
        if (!is_file($source)) {
            throw new \Exception('Could not find source file at "'.$source.'".');
        }

        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function sendToBrowser(bool $sendToBrowser = false, bool $inline = false): self
    {
        $this->sendToBrowser = $sendToBrowser;
        $this->sendToBrowserInline = $inline;

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

    /**
     * @throws \Exception
     */
    public function convertTo(string $format, string $targetPath = null): string
    {
        if (!is_file($this->source)) {
            throw new \Exception('Could not find source file at "'.$this->source.'".');
        }

        $this->format = strtolower(ltrim($format, '.'));

        if ($targetPath) {
            $this->setTargetPath($targetPath);
        }

        // Start conversion process
        $pathConvFile = $this->convert();

        if ($this->sendToBrowser) {
            $this->sendFileToBrowser($pathConvFile, '', $this->sendToBrowserInline);
        }

        $this->reset();

        return $pathConvFile;
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

    /**
     * @throws \Exception
     */
    protected function convert(): string
    {
        if (null === $this->getTargetPath()) {
            $targetPath = sprintf(
                '%s/%s.%s',
                \dirname($this->source),
                pathinfo($this->source, PATHINFO_FILENAME),
                $this->format,
            );
            $this->setTargetPath($targetPath);
        }

        // Convert file to the target format if it can not be found in the cache.
        if (!is_file($this->getTargetPath()) || $this->uncached) {
            $cloudconvert = new CloudConvert([
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

            $cloudconvert->jobs()->create($job);

            // Get upload task
            $uploadTask = $job->getTasks()
                ->whereName(self::IMPORT_FILE_TASK_NAME)[0]
            ;

            // Upload file to the cloudconvert server
            $cloudconvert->tasks()
                ->upload($uploadTask, fopen($this->source, 'r'), basename($this->source))
            ;

            // Wait for job completion
            $cloudconvert->jobs()->wait($job);

            $file = $job->getExportUrls();

            if (!\is_array($file) || null === $file[0]) {
                throw new \Exception('File conversion failed.');
            }

            $source = $cloudconvert
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
            stream_copy_to_stream($source, $dest);

            // Contao log
            $this->contaoLogger->log(
                sprintf(
                    'Successfully converted "%s" to "%s" using the cloudconvert api.',
                    $this->source,
                    $this->getTargetPath()
                ),
                __METHOD__,
            );
        }

        return $this->getTargetPath();
    }

    protected function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    protected function getImportTask(): Task
    {
        return new Task('import/upload', self::IMPORT_FILE_TASK_NAME);
    }

    protected function getConversionTask(): Task
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

    protected function getExportTask(): Task
    {
        return (new Task('export/url', self::EXPORT_TASK_NAME))
            ->set('input', self::CONVERSION_TASK_NAME)
        ;
    }

    /**
     * @throws \Exception
     */
    protected function setTargetPath(string $targetPath): void
    {
        // Create the folder if it doesn't exist
        if (!is_dir(\dirname($targetPath))) {
            mkdir(\dirname($targetPath), 0775, true);
        }

        if (!is_dir(\dirname($targetPath))) {
            throw new \Exception(sprintf('Unable to create target folder "%s"', \dirname($targetPath)));
        }

        $this->targetPath = $targetPath;
    }

    protected function sendFileToBrowser(string $filePath, string $filename = '', bool $inline = false): void
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

        throw new ResponseException($response);
    }
}
