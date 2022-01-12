<p align="center">
<a href="https://github.com/markocupic"><img src="https://github.com/markocupic/markocupic/blob/main/logo.png?raw=true" width="200"></a>
</p>

# Cloudconvert Bundle
This simple bundle for the Contao CMS provides a wrapper to convert files from one format into another using the [Cloudconvert API](https://cloudconvert.com/api/v2).
<p><a href="https://cloudconvert.com/"><img src="docs/images/logo_cloudconvert.png" width="200"></a></p>

Get your free api key for using the Cloudconvert PHP API: [Free Plan Cloudconvert](https://cloudconvert.com/pricing)

## Configuration
```yaml
# config/config.yml

markocupic_cloudconvert:
  api_key: '****'
```

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

        // Basic example:
        // Convert from docx to pdf (minimal configuration)
        $this->convertFile
            ->file($sourcePath)
            ->convertTo('pdf')
        ;

        // A slightly more sophisticated example:
        // Convert from docx to jpg, send file to the browser
        // and set some more options
        $this->convertFile
            ->reset()
            ->file($sourcePath)
            ->uncached(false)
            ->sendToBrowser(true, true)
            // For a full list of possible options
            // please visit https://cloudconvert.com/api/v2/convert#convert-tasks
            ->setOption('width', 1200)
            ->setOption('quality', 90)
            // For a full list of the supported formats
            // please visit https://cloudconvert.com/api/v2/convert#convert-formats
            ->convertTo('jpg', 'files/images/my.jpg')
        ;

        return new Response('Successfully run two conversion tasks.');
    }
}


```

Have fun!

