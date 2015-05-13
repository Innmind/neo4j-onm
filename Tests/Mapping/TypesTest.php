<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\Types;

class TypesTest extends \PHPUnit_Framework_TestCase
{
    public function testAddType()
    {
        Types::addType('foo', 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\StringType');

        $this->assertInstanceOf(
            'Innmind\\Neo4j\\ONM\\Mapping\\Type\\StringType',
            Types::getType('foo')
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidTypeException
     */
    public function testThrowWhenAddingInvalidType()
    {
        Types::addType('bar', 'stdClass');
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidTypeException
     */
    public function testThrowWhenGettingUnknwonType()
    {
        Types::getType('foobar');
    }
}
