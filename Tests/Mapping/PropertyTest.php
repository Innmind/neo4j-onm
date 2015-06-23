<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testSetName()
    {
        $p = new Property;

        $this->assertSame($p, $p->setName('foo'));
        $this->assertEquals('foo', $p->getName());
    }

    public function testGetDefaultType()
    {
        $p = new Property;

        $this->assertEquals('string', $p->getType());
    }

    public function testSetType()
    {
        $p = new Property;

        $this->assertSame($p, $p->setType('int'));
        $this->assertEquals('int', $p->getType());
    }

    public function testSetNullable()
    {
        $p = new Property;

        $this->assertTrue($p->isNullable());
        $this->assertSame($p, $p->setNullable(false));
        $this->assertFalse($p->isNullable());
        $this->assertSame($p, $p->setNullable(true));
        $this->assertTrue($p->isNullable());
    }

    public function testAddOption()
    {
        $p = new Property;

        $this->assertFalse($p->hasOption('foo'));
        $this->assertSame($p, $p->addOption('foo', 'bar'));
        $this->assertTrue($p->hasOption('foo'));
        $this->assertEquals('bar', $p->getOption('foo'));
    }

    public function testGetOptions()
    {
        $p = new Property;
        $p->addOption('foo', 'bar');

        $this->assertEquals(
            ['foo' => 'bar'],
            $p->getOptions()
        );
    }
}
