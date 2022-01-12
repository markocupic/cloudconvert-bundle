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

## Free plan
Get your **free API key** for using
  the **Cloudconvert API**: [Free Plan Cloudconvert](https://cloudconvert.com/pricing)

## Installation & configuration
Install the extension via the **Contao Manager** or
  call `composer require markocupic/cloudconvert-bundle` in your **command line**.

In your `config/config.yml` you now have to set the **api key**.

```yaml
# config/config.yml
markocupic_cloudconvert:
  api_key: '****' # mandatory
  sandbox_api_key: '****' # optional
```

To complete the installation please run in your command line `composer install`.

## Usage
```php
<?php

// src/Controller/CloudconvertDemoController.php

declare(strict_types=1);

namespace App\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Markocupic\CloudconvertBundle\Conversion\ConvertFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cloudconvert_demo", name=CloudconvertDemoController::class, defaults={"_scope" = "frontend"})
 */
class CloudconvertDemoController extends AbstractController
{

    private ContaoFramework $framework;
    private ConvertFile $convertFile;

    public function __construct(ContaoFramework $framework, ConvertFile $convertFile)
    {
        $this->framework = $framework;
        $this->convertFile = $convertFile;
    }

    /**
     * @throws \Exception
     */
    public function __invoke(): Response
    {

        $this->framework->initialize(true);
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        $sourcePath = $projectDir.'/files/mswordfile.docx';

        // Basic example (minimal configuration):
        // Convert from docx to pdf
        $this->convertFile
            ->file($sourcePath)
            // Save converted file in the same
            // directory like the source file.
            ->convertTo('pdf')
        ;

        $sourcePath = $projectDir.'/files/samplesound.wav';

        // Convert from wav to mp3 and send file
        // to the browser.
        $this->convertFile
            ->reset()
            ->file($sourcePath)
            ->sendToBrowser(true, true)
            ->convertTo('mp3')
        ;

        $sourcePath = $projectDir.'/files/image.jpg';

        // A slightly more sophisticated example:
        $this->convertFile
            ->reset()
            ->file($sourcePath)
            // Sandbox API key has to be set in config/config.yml
            ->sandbox(true)
            ->uncached(true)
            ->sendToBrowser(true, true)
            // For a full list of possible options
            // please visit https://cloudconvert.com/api/v2/convert#convert-tasks
            ->setOption('width', 1200)
            ->setOption('quality', 90)
            // Convert docx file into the png format and
            // save file in a different directory.
            // For a full list of supported formats
            // please visit https://cloudconvert.com/api/v2/convert#convert-formats
            ->convertTo('png', 'files/images/mynewimage.png')
        ;

        return new Response('Successfully run two conversion tasks.');
    }
}


```

Have fun!

