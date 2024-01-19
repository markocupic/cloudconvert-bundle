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
    public function getConfigTreeBuilder()
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
            ->end()
        ;

        return $treeBuilder;
    }
}
