<?php

namespace Innmind\Neo4j\ONM\Mapping\Reader;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class FileConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder;
        $root = $builder->root('neo4j_entity_mapping');

        $root
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->enumNode('type')
                        ->isRequired()
                        ->values(['node', 'relationship'])
                    ->end()
                    ->scalarNode('repository')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('alias')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('id')
                        ->useAttributeAsKey('name')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                ->scalarNode('type')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('generator')
                                    ->children()
                                        ->enumNode('strategy')
                                            ->values(['AUTO', 'UUID'])
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('labels')
                        ->canBeUnset()
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('rel_type')->end()
                    ->arrayNode('properties')
                        ->useAttributeAsKey('name')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->prototype('variable')->end()
                            ->beforeNormalization()
                                ->always()
                                ->then(function ($v) {
                                    if (is_string($v)) {
                                        return ['type' => $v];
                                    }

                                    if (!isset($v['type']) || empty($v['type'])) {
                                        throw new InvalidConfigurationException(
                                            'A type must be set for a property'
                                        );
                                    }

                                    return $v;
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->always()
                    ->then(function ($v) {
                        if (!isset($v['type']) || empty($v['type'])) {
                            throw new InvalidConfigurationException(
                                'An entity type must be defined'
                            );
                        }

                        if (
                            $v['type'] === 'node' &&
                            (!isset($v['labels']) || empty($v['labels']))
                        ) {
                            throw new InvalidConfigurationException(
                                'At least one label must be set for a node'
                            );
                        }

                        if (
                            $v['type'] === 'relationship' &&
                            (!isset($v['rel_type']) || empty($v['rel_type']))
                        ) {
                            throw new InvalidConfigurationException(
                                'A "rel_type" must be defined for a relationship'
                            );
                        }

                        return $v;
                    })
                ->end()
            ->end();

        return $builder;
    }
}
