# Cloudconvert Bundle
This simple extension for the Contao CMS provides a service to convert .docx files to .pdf files.

Get your free API key for using the Cloudconvert PHP API: [Free Plan Cloudconvert](https://cloudconvert.com/pricing) 

```php
$source = 'files/mswordfile.docx';

(new \Markocupic\CloudconvertBundle\Services\DocxToPdfConversion($source, \Contao\Config::get('cloudconvertApiKey')))
  ->sendToBrowser(true)
  ->createUncached(false)
  ->convert()
;

// Version 2.x.x uses the Cloudconvert version 2 api.
```


