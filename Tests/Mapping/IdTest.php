<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\Id;

class IdTest extends \PHPUnit_Framework_TestCase
{
    public function testSetProperty()
    {
        $i = new Id;

        $this->assertEquals($i, $i->setProperty('id'));
        $this->assertEquals('id', $i->getProperty());
    }

    public function testSetType()
    {
        $i = new Id;

        $this->assertEquals($i, $i->setType('string'));
        $this->assertEquals('string', $i->getType());
    }

    public function testSetStrategy()
    {
        $i = new Id;

        $this->assertEquals($i, $i->setStrategy('AUTO'));
        $this->assertEquals('AUTO', $i->getStrategy());
    }
}
