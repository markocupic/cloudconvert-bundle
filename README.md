<p align="center"><a href="https://github.com/markocupic"><img src="https://github.com/markocupic/markocupic/blob/main/logo.png?raw=true" width="200"></a></p>



# Cloudconvert Bundle
This simple extension for the Contao CMS provides a service to convert .docx files to .pdf files.

Get your free api key for using the Cloudconvert PHP API: [Free Plan Cloudconvert](https://cloudconvert.com/pricing)

## Configuration
```yaml
# config/config.yml

markocupic_cloudconvert:
  api_key: '****'
```

## Usage
```php
$source = 'files/mswordfile.docx';

(new \Markocupic\CloudconvertBundle\Services\DocxToPdfConversion($source))
  ->sendToBrowser(true)
  ->createUncached(false)
  ->convert()
;

// Version 2.x.x uses the Cloudconvert version 2 api.
```


