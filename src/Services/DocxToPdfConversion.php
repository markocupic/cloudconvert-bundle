<?php

declare(strict_types=1);

namespace Markocupic\CloudconvertBundle\Services;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Contao\Controller;
use Contao\Environment;
use Contao\Folder;
use Contao\File;
use Contao\System;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class DocxToPdfConversion
{

    protected $apiKey;
    protected File $objDocxSrc;
    protected bool $sendToBrowser = false;
    protected bool $createUncached = false;

    /**
     * @throws \Exception
     */
    public function __construct(string $docxSrcPath, string $apiKey)
    {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!file_exists($projectDir.'/'.$docxSrcPath)) {
            throw new FileNotFoundException(sprintf('Docx file "%s" not found. Conversion aborted.', $docxSrcPath));
        }

        $this->objDocxSrc = new File($docxSrcPath);
        $this->apiKey = $apiKey;
    }


    public function sendToBrowser(bool $blnSendToBrowser = false): self
    {
        $this->sendToBrowser = $blnSendToBrowser;

        return $this;
    }


    public function createUncached(bool $blnUncached = false): self
    {
        $this->createUncached = $blnUncached;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function convert(): void
    {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $pdfSrc = dirname($this->objDocxSrc->path).'/'.$this->objDocxSrc->filename.'.pdf';

        // Convert docx file to pdf if it can not bee found in the cache
        if (!is_file($projectDir.'/'.$pdfSrc) || $this->createUncached === true) {
            // Be sure the folder exists
            new Folder(dirname($pdfSrc));

            $cloudconvert = new CloudConvert([
                'api_key' => $this->apiKey,
                'sandbox' => false,
            ]);

            $job = (new Job())
                ->setTag('docx2Pdf-Job')
                ->addTask(
                    (new Task('import/raw', 'importDocx-File'))
                        ->set('file', file_get_contents($projectDir.'/'.$this->objDocxSrc))
                        ->set('filename', $this->objDocxSrc->basename)
                )
                ->addTask(
                    (new Task('convert', 'docx2Pdf-Task'))
                        ->set('input', 'importDocx-File')
                        ->set('output_format', 'pdf')
                )
                ->addTask(
                    (new Task('export/url', 'exportPdf-File'))
                        ->set('input', 'docx2Pdf-Task')
                )
            ;

            $cloudconvert->jobs()->create($job);
            $cloudconvert->jobs()->wait($job); // Wait for job completion

            $file = $job->getExportUrls()[0];
            $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();

            if (file_exists($projectDir.'/'.$pdfSrc)) {
                unlink($projectDir.'/'.$pdfSrc);
            }

            $dest = fopen($projectDir.'/'.$pdfSrc, 'w');
            stream_copy_to_stream($source, $dest);
        }

        if ($this->sendToBrowser) {

            // Send converted file to the browser
            sleep(1);
            $objPdf = new File($pdfSrc);
            $objPdf->sendToBrowser();
        }
    }
}
