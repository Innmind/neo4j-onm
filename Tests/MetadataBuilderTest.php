<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\{
    MetadataBuilder,
    Types,
    Metadatas,
    Metadata\AggregateRoot,
    Metadata\Relationship
};
use Symfony\Component\Yaml\Yaml;

class MetadataBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $b;

    public function setUp()
    {
        $this->b = new MetadataBuilder(new Types);
    }

    public function testContainer()
    {
        $this->assertInstanceOf(
            Metadatas::class,
            $this->b->container()
        );
        $this->assertSame(
            $this->b->container(),
            $this->b->container()
        );
    }

    public function testInject()
    {
        $conf = Yaml::parse(file_get_contents('fixtures/mapping.yml'));

        $this->assertSame(0, $this->b->container()->all()->size());
        $this->assertSame(
            $this->b,
            $this->b->inject($conf)
        );
        $this->assertSame(2, $this->b->container()->all()->size());
        $this->assertInstanceOf(
            AggregateRoot::class,
            $this->b->container()->get('Image')
        );
        $this->assertInstanceOf(
            AggregateRoot::class,
            $this->b->container()->get('I')
        );
        $this->assertInstanceOf(
            Relationship::class,
            $this->b->container()->get('SomeRelationship')
        );
        $this->assertInstanceOf(
            Relationship::class,
            $this->b->container()->get('SR')
        );
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "type" at path "neo4j_entity_mapping.foo" must be configured.
     */
    public function testThrowWhenConfigurationFormatNotRespected()
    {
        $this->b->inject(['foo' => []]);
    }
}
