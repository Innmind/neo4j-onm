<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Reader;

use Symfony\Component\Config\Definition\Processor;
use Innmind\Neo4j\ONM\Mapping\Reader\FileConfiguration;

class FileConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected $processor;
    protected $config;

    public function setUp()
    {
        $this->processor = new Processor;
        $this->config = new FileConfiguration;
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage An entity type must be defined
     */
    public function testThrowIfNoTypeSet()
    {
        $content = [
            'Entity' => [],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The value "foo" is not allowed for path "neo4j_entity_mapping.Entity.type". Permissible values: "node", "relationship"
     */
    public function testThrowIfInvalidType()
    {
        $content = [
            'Entity' => [
                'type' => 'foo',
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage At least one label must be set for a node
     */
    public function testThrowIfNoLabelForNode()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => [],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "neo4j_entity_mapping.Entity.repository" cannot contain an empty value, but got null.
     */
    public function testThrowIfEmptyRepository()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => ['Foo'],
                'repository' => null,
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "neo4j_entity_mapping.Entity.alias" cannot contain an empty value, but got null.
     */
    public function testThrowIfEmptyAlias()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => ['Foo'],
                'alias' => null,
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "neo4j_entity_mapping.Entity.id" should have at least 1 element(s) defined.
     */
    public function testThrowIfNoIdSet()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => ['Foo'],
                'id' => [],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "type" at path "neo4j_entity_mapping.Entity.id.uuid" must be configured.
     */
    public function testThrowIfNoIdTypeSet()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => ['Foo'],
                'id' => [
                    'uuid' => [],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The value "foo" is not allowed for path "neo4j_entity_mapping.Entity.id.uuid.generator.strategy". Permissible values: "AUTO", "UUID"
     */
    public function testThrowIfInvalidIdStrategy()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'labels' => ['Foo'],
                'id' => [
                    'uuid' => [
                        'type' => 'string',
                        'generator' => [
                            'strategy' => 'foo',
                        ],
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage A "rel_type" must be defined for a relationship
     */
    public function testThrowIfNoRelTypeSet()
    {
        $content = [
            'Entity' => [
                'type' => 'relationship',
                'rel_type' => null,
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The path "neo4j_entity_mapping.Entity.properties" should have at least 1 element(s) defined.
     */
    public function testThrowIfNoProperties()
    {
        $content = [
            'Entity' => [
                'type' => 'relationship',
                'rel_type' => 'foo',
                'properties' => [],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage A type must be set for a property
     */
    public function testThrowIfNoTypeSetForAProperty()
    {
        $content = [
            'Entity' => [
                'type' => 'relationship',
                'rel_type' => 'foo',
                'properties' => [
                    'foo' => [
                        'collection' => true,
                    ],
                ],
            ],
        ];

        $this->processor->processConfiguration($this->config, [$content]);
    }

    public function testProcessNode()
    {
        $content = [
            'Entity' => [
                'type' => 'node',
                'repository' => 'Foo',
                'alias' => 'E',
                'id' => [
                    'uuid' => [
                        'type' => 'string',
                        'generator' => [
                            'strategy' => 'UUID',
                        ],
                    ],
                ],
                'labels' => ['Foo'],
                'properties' => [
                    'foo' => 'string',
                    'bar' => [
                        'type' => 'int',
                        'precision' => 42
                    ],
                ],
            ],
        ];

        $expected = $content;
        $expected['Entity']['properties']['foo'] = ['type' => 'string'];

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration($this->config, [$content])
        );
    }

    public function testProcessRelationship()
    {
        $content = [
            'Entity' => [
                'type' => 'relationship',
                'rel_type' => 'Foo',
                'id' => [
                    'uuid' => [
                        'type' => 'string',
                        'generator' => [
                            'strategy' => 'UUID',
                        ],
                    ],
                ],
                'properties' => [
                    'foo' => 'string',
                    'bar' => [
                        'type' => 'int',
                        'precision' => 42
                    ],
                ],
            ],
        ];

        $expected = $content;
        $expected['Entity']['properties']['foo'] = ['type' => 'string'];
        $expected['Entity']['labels'] = [];

        $this->assertEquals(
            $expected,
            $this->processor->processConfiguration($this->config, [$content])
        );
    }
}
