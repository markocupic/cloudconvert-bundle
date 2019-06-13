<?php
/**
 * Cloudconvert helper classes
 * Copyright (c) 2008-2019 Marko Cupic
 * @package cloudconvert-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2019
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

/**
 * Class Plugin
 * @package Markocupic\CloudconvertBundle\ContaoManager
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('Markocupic\CloudconvertBundle\MarkocupicCloudconvertBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
        ];
    }
}
