<p align="center"><a href="https://github.com/markocupic"><img src="https://github.com/markocupic/markocupic/blob/main/logo.png?raw=true" width="200"></a></p>

# Cloudconvert Bundle
This simple extension for the Contao CMS provides a service to convert .docx files to .pdf files using the [Cloudconvert API](https://cloudconvert.com/api/v2).

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

declare(strict_types=1);

namespace App\Controller;

class DemoController
{

    protected ConvertFile $convertFile;

    public function __construct(ConvertFile $convertFile){
        $this->convertFile = $convertFile;
    }

    public function demo(){
        $source = 'files/mswordfile.docx';

        // Convert from docx to pdf (minimal configuration)
        $this->fileConverter
            ->file($source)
            ->convertTo('pdf')
            ;

        // Convert from docx to jpg, send file to the browser and some more options
        $this->fileConverter
            ->file($source)
            ->uncached(false)
            ->sendToBrowser(true)
            // For a full list visit https://cloudconvert.com/api/v2/convert#convert-tasks
            ->setOption('width', 1200)
            ->setOption('quality', 90)
            // Supported formats: https://cloudconvert.com/api/v2/convert#convert-formats
            ->convertTo('jpg', 'files/images/my.jpg')
            ;
    }
}

```

Have fun!

