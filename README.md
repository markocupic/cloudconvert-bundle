# Cloudconvert Bundle

Get your free API key for using the Cloudconvert PHP API: [Free Plan Cloudconvert](https://cloudconvert.com/pricing) 

## DocxToPdfConversion
Convert MS Word files int pdf files, using the Cloudconvert PHP API.

```php
$destFilename = 'files/mswordfile.docx';

$objConversion = new \Markocupic\CloudconvertBundle\Services\DocxToPdfConversion($destFilename, Config::get('cloudconvertApiKey'));
$objConversion->sendToBrowser(true)->createUncached(false)->convert();


```


