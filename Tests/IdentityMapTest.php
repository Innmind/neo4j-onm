<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\IdentityMap;

class IdentityMapTest extends \PHPUnit_Framework_TestCase
{
    public function testAddClass()
    {
        $i = new IdentityMap;

        $this->assertSame($i, $i->addClass('stdClass'));
        $this->assertTrue($i->has('stdClass'));
    }

    public function testAddAlias()
    {
        $i = new IdentityMap;

        $i->addClass('stdClass');

        $this->assertSame($i, $i->addAlias('foo', 'stdClass'));
        $this->assertTrue($i->has('stdClass'));
        $this->assertTrue($i->has('foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\IdentityException
     */
    public function testThrowIfAliasAlreadyUsed()
    {
        $i = new IdentityMap;
        $i->addAlias('foo', 'A');
        $i->addAlias('foo', 'B');
    }

    public function testGetClass()
    {
        $i = new IdentityMap;
        $i->addAlias('foo', 'stdClass');

        $this->assertEquals('stdClass', $i->getClass('stdClass'));
        $this->assertEquals('stdClass', $i->getClass('foo'));
    }

    public function testGetAlias()
    {
        $i = new IdentityMap;
        $i->addAlias('foo', 'stdClass');

        $this->assertEquals('foo', $i->getAlias('stdClass'));
        $this->assertEquals('A', $i->getAlias('A'));
    }
}
