<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license LGPL-3.0+
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MarkocupicCloudconvertExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');

        // Configuration
        $container->setParameter('markocupic_cloudconvert.api_key', $config['api_key']);
        $container->setParameter('markocupic_cloudconvert.sandbox_api_key', $config['sandbox_api_key']);
        $container->setParameter('markocupic_cloudconvert.cache_dir', $config['cache_dir']);
    }
}
