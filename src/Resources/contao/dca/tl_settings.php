<?php
/**
 * Cloudconvert helper classes
 * Copyright (c) 2008-2019 Marko Cupic
 * @package cloudconvert-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2019
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('cloudconvert_legend', 'global_legend')
    ->addField(array('cloudconvertApiKey'), 'cloudconvert_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings');

$GLOBALS['TL_DCA']['tl_settings']['fields']['cloudconvertApiKey'] = array(

    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['cloudconvertApiKey'],
    'inputType' => 'text',
    'eval'      => array('mandatory' => true, 'decodeEntities' => false, 'tl_class' => 'w50'),
);
