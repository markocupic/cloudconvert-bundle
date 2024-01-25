<p align="left">
<a href="https://github.com/markocupic"><img src="https://github.com/markocupic/markocupic/blob/main/logo.png?raw=true" width="200"></a>
</p>

# Cloudconvert Bundle
This simple bundle for the **Contao CMS** provides an OOP PHP wrapper for
  converting files from one format into another using the [Cloudconvert API](https://cloudconvert.com/api/v2).
<p><a href="https://cloudconvert.com/"><img src="docs/images/logo_cloudconvert.png" width="200"></a></p>

Almost everything is possible:
- docx -> pdf
- jpeg -> png
- wav -> mp3
- csv -> xlsx
- etc. For a full list of formats visit [Cloudconvert](https://cloudconvert.com/).

## Free plan (25 credits per day)
Get your **free API key** for using
  the **Cloudconvert API**: [Free Plan Cloudconvert](https://cloudconvert.com/pricing)

## Installation & configuration
Install the extension via the **Contao Manager** or
  call `composer require markocupic/cloudconvert-bundle` in your **command line**.

In your `config/config.yaml` you now have to set the **api key**.

```yaml
# config/config.yaml
markocupic_cloudconvert:
  api_key: '****' # mandatory
  sandbox_api_key: '****' # optional
  credit_expiration_notification: # optional
      enabled: true # optional
      limit: 150 # optional
      email: ['foo@bar.ch'] # optional
```

To complete the installation please run `composer install` in your command line.

## Usage

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Markocupic\CloudconvertBundle\Conversion\ConvertFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cloudconvert_demo', name: CloudconvertDemoController::class, defaults: ['_scope' => 'frontend'])]
class CloudconvertDemoController extends AbstractController
{

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ConvertFile $convertFile,
    ){}

    /**
     * @throws \Exception
     */
    public function __invoke(): BinaryFileResponse
    {

        $this->framework->initialize();
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        $sourcePath = $projectDir.'/files/mswordfile.docx';

        // Basic example (minimal configuration):
        // Convert from docx to pdf
        $objSplFile = $this->convertFile
            ->file($sourcePath)
            // Save converted file in the same
            // directory as the source file.
            ->convertTo('pdf')
        ;

        $sourcePath = $projectDir.'/files/samplesound.wav';

        // Convert from wav to mp3
        $objSplFile = $this->convertFile
            ->reset()
            ->file($sourcePath)
            ->convertTo('mp3')
        ;

        $sourcePath = $projectDir.'/files/image.jpg';

        // A slightly more sophisticated example:
        $objSplFile = $this->convertFile
            ->reset()
            ->file($sourcePath)
            // Sandbox API key has to be set in config/config.yaml
            ->sandbox(true)
            ->uncached(false) // Enable cache
            ->setCacheHashCode('566TZZUUTTAGHJKUZT') // use the hash of your file to get the file from the cache directory
            // For a full list of possible options
            // please visit https://cloudconvert.com/api/v2/convert#convert-tasks
            ->setOption('width', 1200)
            ->setOption('quality', 90)
            // Convert docx file into the png format and
            // save file in a different directory.
            // For a full list of supported formats
            // please visit https://cloudconvert.com/api/v2/convert#convert-formats
            ->convertTo('png', 'files/images/my_new_image.png')
        ;

        // Send the converted file to the browser
        return $this->file($objSplFile->getRealPath());
    }
}


```
