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

declare(strict_types=1);

namespace App\Controller;

use Contao\File;
use Markocupic\CloudconvertBundle\Conversion\ConvertFile;

class DemoController
{

    protected ConvertFile $convertFile;

    public function __construct(ConvertFile $convertFile){
        $this->convertFile = $convertFile;
    }

    public function demo(){
        $source = new File('files/mswordfile.docx');

        // Basic example:
        // Convert from docx to pdf (minimal configuration)
        $this->fileConverter
            ->file($source)
            ->convertTo('pdf')
            ;

        // A slightly more sophisticated example:
        // Convert from docx to jpg, send file to the browser
        // and set some more options
        $this->fileConverter
            ->reset()
            ->file($source)
            ->uncached(false)
            ->sendToBrowser(true)
            // For a full list of possible options
            // please visit https://cloudconvert.com/api/v2/convert#convert-tasks
            ->setOption('width', 1200)
            ->setOption('quality', 90)
            // For a full list of the supported formats
            // please visit https://cloudconvert.com/api/v2/convert#convert-formats
            ->convertTo('jpg', 'files/images/my.jpg')
            ;
    }
}

```

Have fun!

