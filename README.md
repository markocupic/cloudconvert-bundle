# Cloudconvert Bundle

## DocxToPdfConversion

```php
$destFilename = 'files/mswordfile.docx';
$objConversion = new \Markocupic\CloudconvertBundle\Services\DocxToPdfConversion($destFilename, \Contao\Config::get('cloudconvertApiKey'));
$objConversion->sendToBrowser(true)->createUncached(true)->convert();

```


