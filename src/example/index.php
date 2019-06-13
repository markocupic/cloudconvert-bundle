<?php

$destFilename = 'files/mswordfile.docx';
$objConversion = new DocxToPdfConversion($destFilename, \Contao\Config::get('cloudconvertApiKey'));
$objConversion->sendToBrowser(true)->createUncached(true)->convert();
