<?php

namespace Innmind\Neo4j\ONM\Mapping\Reader;

use Innmind\Neo4j\ONM\Generators;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
                    ->append($this->addIdNode())
                    ->arrayNode('labels')
                        ->canBeUnset()
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('rel_type')->end()
                    ->append($this->addPropertiesNode())
                ->end()
                ->beforeNormalization()
                    ->always()
                    ->then(function(array $data) {
                        if (!isset($data['type']) || empty($data['type'])) {
                            throw new InvalidConfigurationException(
                                'An entity type must be defined'
                            );
                        }

                        $required = [
                            'node' => ['labels'],
                            'relationship' => ['rel_type'],
                        ];

                        foreach ($required as $type => $requirements) {
                            foreach ($requirements as $req) {
                                if ($data['type'] === $type && (!isset($data[$req]) || empty($data[$req]))) {
                                    throw new InvalidConfigurationException(sprintf(
                                        'Entity type "%s" requires "%s" to be set and not empty',
                                        $type,
                                        $req
                                    ));
                                }
                            }
                        }

                        return $data;
                    })
                ->end()
            ->end();

        return $builder;
    }

    /**
     * Build the config for the id key
     *
     * @return NodeDefinition
     */
    protected function addIdNode()
    {
        $builder = new TreeBuilder;
        $node = $builder->root('id');

        $node
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
                                ->values(Generators::getStrategies())
                                ->defaultValue('UUID')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * Build the config for the 'properties' key
     *
     * @return NodeDefinition
     */
    protected function addPropertiesNode()
    {
        $builder = new TreeBuilder;
        $node = $builder->root('properties');

        $node
            ->useAttributeAsKey('name')
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->prototype('variable')->end()
                ->beforeNormalization()
                    ->always()
                    ->then(function($v) {
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
            ->end();

        return $node;
    }
}
