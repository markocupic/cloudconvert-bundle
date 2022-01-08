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

class ConvertFile
{
    private string $projectDir;
    private string $apiKey;
    private ?File $file = null;
    private ?string $format = null;
    private bool $sendToBrowser = false;
    private bool $uncached = false;
    private ?string $targetPath = null;

    public function __construct(string $projectDir, string $apiKey)
    {
        $this->projectDir = $projectDir;
        $this->apiKey = $apiKey;
    }

    public function file(File $file): self
    {
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
            // Send converted file to the browser
            sleep(1);
            $objConvertedFile->sendToBrowser();
        }

        return $objConvertedFile;
    }

    /**
     * @throws \Exception
     */
    protected function convert(): File
    {
        if (null === ($targetPath = $this->getTargetPath())) {
            $targetPath = \dirname($this->file->path).'/'.$this->file->filename.'.'.$this->format;
            $this->setTargetPath($targetPath);
        }

        // Convert file to the target format if it can not be found in the cache
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
                    (new Task('convert', 'conversionTask'))
                        ->set('input', 'importFileTask')
                        ->set('output_format', $this->format)
                )
                ->addTask(
                    (new Task('export/url', 'exportTask'))
                        ->set('input', 'conversionTask')
                )
            ;

            $cloudconvert->jobs()->create($job);
            $cloudconvert->jobs()->wait($job); // Wait for job completion

            $file = $job->getExportUrls()[0];
            $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();

            if (file_exists($this->projectDir.'/'.$this->getTargetPath())) {
                unlink($this->projectDir.'/'.$this->getTargetPath());
            }

            $dest = fopen($this->projectDir.'/'.$this->getTargetPath(), 'w');
            stream_copy_to_stream($source, $dest);
        }

        return new File($this->getTargetPath());
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    protected function getTargetPath(): ?string
    {
        return $this->targetPath;
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
