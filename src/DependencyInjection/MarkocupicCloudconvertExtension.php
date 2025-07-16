<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
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
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.yaml');

        // Configuration
        $container->setParameter('markocupic_cloudconvert.api_key', $config['api_key']);
        $container->setParameter('markocupic_cloudconvert.sandbox_api_key', $config['sandbox_api_key']);
        $container->setParameter('markocupic_cloudconvert.cache_dir', $config['cache_dir']);
        $container->setParameter('markocupic_cloudconvert.backend_alert_credit_limit', $config['backend_alert_credit_limit']);
        $container->setParameter('markocupic_cloudconvert.credit_expiration_notification', $config['credit_expiration_notification']);
        $container->setParameter('markocupic_cloudconvert.credit_expiration_notification.enabled', $config['credit_expiration_notification']['enabled']);
        $container->setParameter('markocupic_cloudconvert.credit_expiration_notification.limit', $config['credit_expiration_notification']['limit']);
        $container->setParameter('markocupic_cloudconvert.credit_expiration_notification.email', $config['credit_expiration_notification']['email']);
    }
}
