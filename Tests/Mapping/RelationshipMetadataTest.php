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

        $this->assertEquals($m, $m->setClass('stdClass'));
        $this->assertEquals('stdClass', $m->getClass());
    }

    public function testGetDefaultRepositoryClass()
    {
        $m = new RelationshipMetadata;

        $this->assertEquals(
            'Innmind\\Neo4j\\ONM\\RelationshipRepository',
            $m->getRepositoryClass()
        );
    }

    public function testSetRepositoryClass()
    {
        $m = new RelationshipMetadata;

        $this->assertEquals($m, $m->setRepositoryClass('stdClass'));
        $this->assertEquals('stdClass', $m->getRepositoryClass());
    }

    public function testAddProperty()
    {
        $m = new RelationshipMetadata;
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
        $m = new RelationshipMetadata;
        $id = new Id;

        $this->assertEquals($m, $m->setId($id));
        $this->assertEquals($id, $m->getId());
    }

    public function testSetAlias()
    {
        $m = new RelationshipMetadata;

        $this->assertFalse($m->hasAlias());
        $this->assertEquals($m, $m->setAlias('foo'));
        $this->assertTrue($m->hasAlias());
        $this->assertEquals('foo', $m->getAlias());
    }

    public function testSetType()
    {
        $m = new RelationshipMetadata;

        $this->assertEquals($m, $m->setType('Foo'));
        $this->assertEquals('Foo', $m->getType());
    }
}
