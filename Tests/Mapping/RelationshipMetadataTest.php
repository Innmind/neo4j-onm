<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Id;

class RelationshipMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testSetClass()
    {
        $m = new RelationshipMetadata;

        $this->assertSame($m, $m->setClass('stdClass'));
        $this->assertEquals('stdClass', $m->getClass());
    }

    public function testGetDefaultRepositoryClass()
    {
        $m = new RelationshipMetadata;

        $this->assertEquals(
            'Innmind\\Neo4j\\ONM\\Repository',
            $m->getRepositoryClass()
        );
    }

    public function testSetRepositoryClass()
    {
        $m = new RelationshipMetadata;

        $this->assertSame($m, $m->setRepositoryClass('stdClass'));
        $this->assertEquals('stdClass', $m->getRepositoryClass());
    }

    public function testAddProperty()
    {
        $m = new RelationshipMetadata;
        $p = new Property;
        $p->setName('foo');

        $this->assertFalse($m->hasProperty('foo'));
        $this->assertSame($m, $m->addProperty($p));
        $this->assertTrue($m->hasProperty('foo'));
        $this->assertEquals(
            ['foo' => $p],
            $m->getProperties()
        );
        $this->assertSame(
            $p,
            $m->getProperty('foo')
        );
    }

    public function testSetId()
    {
        $m = new RelationshipMetadata;
        $id = new Id;

        $this->assertSame($m, $m->setId($id));
        $this->assertSame($id, $m->getId());
    }

    public function testSetAlias()
    {
        $m = new RelationshipMetadata;

        $this->assertFalse($m->hasAlias());
        $this->assertSame($m, $m->setAlias('foo'));
        $this->assertTrue($m->hasAlias());
        $this->assertEquals('foo', $m->getAlias());
    }

    public function testSetType()
    {
        $m = new RelationshipMetadata;

        $this->assertSame($m, $m->setType('Foo'));
        $this->assertEquals('FOO', $m->getType());
    }

    public function testSetStartNode()
    {
        $m = new RelationshipMetadata;

        $this->assertFalse($m->hasStartNode());
        $this->assertSame($m, $m->setStartNode('foo'));
        $this->assertTrue($m->hasStartNode());
        $this->assertSame('foo', $m->getStartNode());
    }

    public function testSetEndNode()
    {
        $m = new RelationshipMetadata;

        $this->assertFalse($m->hasEndNode());
        $this->assertSame($m, $m->setEndNode('foo'));
        $this->assertTrue($m->hasEndNode());
        $this->assertSame('foo', $m->getEndNode());
    }

    public function testIsReference()
    {
        $m = new RelationshipMetadata;
        $p = new Property;

        $this->assertFalse($m->isReference($p));
        $p->setType('endNode');
        $this->assertTrue($m->isReference($p));
        $p->setType('startNode');
        $this->assertTrue($m->isReference($p));
    }
}
