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
use Contao\File;
use Contao\Folder;
use Markocupic\CloudconvertBundle\Logger\ContaoLogger;

class ConvertFile
{
    private ContaoLogger $contaoLogger;
    private string $projectDir;
    private string $apiKey;
    private ?File $file = null;
    private ?string $format = null;
    private bool $sendToBrowser = false;
    private bool $uncached = true;
    private ?string $targetPath = null;
    private array $options = [];

    public function __construct(ContaoLogger $contaoLogger, string $projectDir, string $apiKey)
    {
        $this->contaoLogger = $contaoLogger;
        $this->projectDir = $projectDir;
        $this->apiKey = $apiKey;
    }

    public function reset(): self
    {
        $this->file = null;
        $this->format = null;
        $this->sendToBrowser = false;
        $this->uncached = true;
        $this->targetPath = null;
        $this->clearOptions();

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function file(File $file): self
    {
        $this->reset();

        $this->file = $file;

        if (!is_file($this->projectDir.'/'.$file->path)) {
            throw new \Exception('Could not find file '.$this->projectDir.'/'.$file->path.'.');
        }

        return $this;
    }

    public function sendToBrowser(bool $blnSendToBrowser = false): self
    {
        $this->sendToBrowser = $blnSendToBrowser;

        return $this;
    }

    public function uncached(bool $blnUncached = false): self
    {
        $this->uncached = $blnUncached;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function convertTo(string $format, string $targetPath = null): File
    {
        $this->format = strtolower($format);

        if ($targetPath) {
            $this->setTargetPath($targetPath);
        }

        $objConvertedFile = $this->convert();

        if ($this->sendToBrowser) {
            $objConvertedFile->sendToBrowser();
        }

        return $objConvertedFile;
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
    protected function convert(): File
    {
        if (null === $this->getTargetPath()) {
            $targetPath = \dirname($this->file->path).'/'.$this->file->filename.'.'.$this->format;
            $this->setTargetPath($targetPath);
        }

        // Convert file to the target format if it can not be found in the cache.
        if (!is_file($this->projectDir.'/'.$this->getTargetPath()) || $this->uncached) {
            $cloudconvert = new CloudConvert([
                'api_key' => $this->apiKey,
                'sandbox' => false,
            ]);

            $job = (new Job())
                ->setTag('conversionJob')
                ->addTask(
                    (new Task('import/base64', 'importFileTask'))
                        ->set('file', base64_encode(file_get_contents($this->projectDir.'/'.$this->file->path)))
                        ->set('filename', $this->file->basename)
                )
                ->addTask(
                    $this->getConversionTask()
                )
                ->addTask(
                    (new Task('export/url', 'exportTask'))
                        ->set('input', 'conversionTask')
                )
            ;

            $cloudconvert->jobs()->create($job);
            $cloudconvert->jobs()->wait($job); // Wait for job completion

            $file = $job->getExportUrls();

            if (!\is_array($file) || null === $file[0]) {
                throw new \Exception('File conversion failed.');
            }

            $source = $cloudconvert->getHttpTransport()->download($file[0]->url)->detach();

            if (file_exists($this->projectDir.'/'.$this->getTargetPath())) {
                unlink($this->projectDir.'/'.$this->getTargetPath());
            }

            // Save file to the target directory
            $dest = fopen($this->projectDir.'/'.$this->getTargetPath(), 'w');
            stream_copy_to_stream($source, $dest);

            // Contao log
            $this->contaoLogger->log(
                sprintf(
                    'Successfully converted "%s" to "%s" using the cloudconvert api.',
                    $this->file->path,
                    $this->getTargetPath()
                ),
                __METHOD__,
            );
        }

        return new File($this->getTargetPath());
    }

    protected function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    protected function getConversionTask(): Task
    {
        $task = (new Task('convert', 'conversionTask'))
            ->set('input', 'importFileTask')
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

    /**
     * @throws \Exception
     */
    protected function setTargetPath(string $targetPath): void
    {
        // Create the folder if it doesn't exist
        new Folder(\dirname($targetPath));

        if (!is_dir($this->projectDir.'/'.\dirname($targetPath))) {
            throw new \Exception(sprintf('Unable to create target folder "%s"', $this->projectDir.'/'.\dirname($targetPath)));
        }

        $this->targetPath = $targetPath;
    }
}
