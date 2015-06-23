<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\ArrayType;
use Innmind\Neo4j\ONM\Mapping\Property;

class ArrayTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\IncompletePropertyDefinitionException
     */
    public function testThrowWhenNoInnerTypeDefined()
    {
        $a = new ArrayType;
        $p = new Property;

        $a->convertToDatabaseValue(['42'], $p);
    }

    public function testConvertToDatabaseValue()
    {
        $a = new ArrayType;
        $p = new Property;
        $p->addOption('inner_type', 'int');

        $this->assertEquals(
            [42],
            $a->convertToDatabaseValue(['42'], $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $a = new ArrayType;
        $p = new Property;
        $p->addOption('inner_type', 'string');

        $this->assertEquals(
            ['42'],
            $a->convertToPHPValue([42], $p)
        );
    }

    /**
     * @expectedException LogicException
     */
    public function testThrowWhenArrayAsInnerType()
    {
        $a = new ArrayType;
        $p = new Property;
        $p->addOption('inner_type', 'array');

        $a->convertToPHPValue([42], $p);
    }
}
