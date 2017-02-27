<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    MetadataBuilder,
    Types,
    Metadatas,
    Metadata\Aggregate,
    Metadata\Relationship
};
use Innmind\Immutable\Map;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

class MetadataBuilderTest extends TestCase
{
    private $builder;

    public function setUp()
    {
        $this->builder = new MetadataBuilder(new Types);
    }

    public function testContainer()
    {
        $this->assertInstanceOf(
            Metadatas::class,
            $this->builder->container()
        );
        $this->assertSame(
            $this->builder->container(),
            $this->builder->container()
        );
    }

    public function testInject()
    {
        $conf = Yaml::parse(file_get_contents('fixtures/mapping.yml'));

        $this->assertCount(0, $this->builder->container()->all());
        $this->assertSame(
            $this->builder,
            $this->builder->inject([$conf])
        );
        $this->assertCount(2, $this->builder->container()->all());
        $this->assertInstanceOf(
            Aggregate::class,
            $this->builder->container()->get('Image')
        );
        $this->assertInstanceOf(
            Aggregate::class,
            $this->builder->container()->get('I')
        );
        $this->assertInstanceOf(
            Relationship::class,
            $this->builder->container()->get('SomeRelationship')
        );
        $this->assertInstanceOf(
            Relationship::class,
            $this->builder->container()->get('SR')
        );
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "type" at path "neo4j_entity_mapping.foo" must be configured.
     */
    public function testThrowWhenConfigurationFormatNotRespected()
    {
        $this->builder->inject([['foo' => []]]);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidFactoriesMap()
    {
        new MetadataBuilder(
            new Types,
            new Map('string', 'object')
        );
    }
}
