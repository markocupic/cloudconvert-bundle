# Cloudconvert Bundle

## DocxToPdfConversion

```php
$destFilename = 'files/mswordfile.docx';

$objConversion = new \Markocupic\CloudconvertBundle\Services\DocxToPdfConversion($destFilename, Config::get('cloudconvertApiKey'));
$objConversion->sendToBrowser(true)->createUncached(false)->convert();


```


