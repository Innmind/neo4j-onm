<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Id;

class NodeMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testSetClass()
    {
        $m = new NodeMetadata;

        $this->assertEquals($m, $m->setClass('stdClass'));
        $this->assertEquals('stdClass', $m->getClass());
    }

    public function testGetDefaultRepositoryClass()
    {
        $m = new NodeMetadata;

        $this->assertEquals(
            'Innmind\\Neo4j\\ONM\\NodeRepository',
            $m->getRepositoryClass()
        );
    }

    public function testSetRepositoryClass()
    {
        $m = new NodeMetadata;

        $this->assertEquals($m, $m->setRepositoryClass('stdClass'));
        $this->assertEquals('stdClass', $m->getRepositoryClass());
    }

    public function testAddProperty()
    {
        $m = new NodeMetadata;
        $p = new Property;
        $p->setName('foo');

        $this->assertEquals($m, $m->addProperty($p));
        $this->assertEquals(
            ['foo' => $p],
            $m->getProperties()
        );
    }

    public function testSetId()
    {
        $m = new NodeMetadata;
        $id = new Id;

        $this->assertEquals($m, $m->setId($id));
        $this->assertEquals($id, $m->getId());
    }

    public function testSetAlias()
    {
        $m = new NodeMetadata;

        $this->assertFalse($m->hasAlias());
        $this->assertEquals($m, $m->setAlias('foo'));
        $this->assertTrue($m->hasAlias());
        $this->assertEquals('foo', $m->getAlias());
    }

    public function testAddLabel()
    {
        $m = new NodeMetadata;

        $this->assertEquals($m, $m->addLabel('Foo'));
        $this->assertEquals(['Foo'], $m->getLabels());
    }
}
