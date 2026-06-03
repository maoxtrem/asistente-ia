<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('asistente_ia');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('base_url')->defaultValue('http://host.docker.internal:8001')->end()
                ->scalarNode('chat_endpoint')->defaultValue('/api/chat')->end()
                ->scalarNode('api_key')->defaultValue('')->end()
                ->floatNode('connect_timeout')->defaultValue(5.0)->end()
                ->floatNode('timeout')->defaultValue(30.0)->end()
                ->arrayNode('default_headers')
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
