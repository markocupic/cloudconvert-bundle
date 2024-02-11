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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('markocupic_cloudconvert');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_key')
                    ->defaultValue('***')
                ->end()
                ->scalarNode('sandbox_api_key')
                    ->defaultValue('***')
                ->end()
                ->scalarNode('sandbox_api_key')
                    ->defaultValue('***')
                ->end()
                ->scalarNode('cache_dir')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%'.'/system/tmp/cloudconvert/cache')
                ->end()
                ->integerNode('backend_alert_credit_limit')->defaultValue(200)->end()
                ->arrayNode('credit_expiration_notification')
                        ->canBeEnabled()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')->defaultFalse()->end()
                            ->integerNode('limit')->defaultValue(0)->end()
                            ->arrayNode('email')
                                ->info('Allows you to add one or more email addresses, that will be notified when the user account, that belongs to the api key is running out of credits.')
                                ->prototype('scalar')->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end() // credit_expiration_notification
                ->end()
        ;

        return $treeBuilder;
    }
}
