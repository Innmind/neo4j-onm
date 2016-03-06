<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Symfony\Component\Config\Definition\{
    ConfigurationInterface,
    Builder\TreeBuilder,
    Builder\NodeDefinition,
    Exception\InvalidConfigurationException
};

class Configuration implements ConfigurationInterface
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
                    ->scalarNode('alias')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('repository')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('factory')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('identity')
                        ->isRequired()
                        ->children()
                            ->scalarNode('property')
                                ->isRequired()
                            ->end()
                            ->scalarNode('type')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('labels')
                        ->canBeUnset()
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('rel_type')->end()
                    ->arrayNode('startNode')
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('property')
                                ->isRequired()
                            ->end()
                            ->scalarNode('type')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('endNode')
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('property')
                                ->isRequired()
                            ->end()
                            ->scalarNode('type')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->createPropertiesNode())
                    ->arrayNode('children')
                        ->canBeUnset()
                        ->useAttributeAsKey('name')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                ->scalarNode('class')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('type')
                                    ->isRequired()
                                ->end()
                                ->booleanNode('collection')
                                    ->defaultFalse()
                                ->end()
                                ->append($this->createPropertiesNode())
                                ->arrayNode('child')
                                    ->children()
                                        ->scalarNode('property')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('class')
                                            ->isRequired()
                                        ->end()
                                        ->arrayNode('labels')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->append($this->createPropertiesNode())
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    private function createPropertiesNode(): NodeDefinition
    {
        $builder = new TreeBuilder;
        $node = $builder->root('properties');

        $node
            ->canBeUnset()
            ->useAttributeAsKey('name')
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->prototype('variable')->end()
                ->beforeNormalization()
                    ->always()
                    ->then(function($value) {
                        if (is_string($value)) {
                            return ['type' => $value];
                        }

                        $type = $value['type'] ?? null;

                        if (empty($type)) {
                            throw new InvalidConfigurationException;
                        }

                        return $value;
                    })
                ->end()
            ->end();

        return $node;
    }
}
