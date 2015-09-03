<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\IntType;
use Innmind\Neo4j\ONM\Mapping\Property;

class IntTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $t = new IntType;
        $p = new Property;

        $this->assertEquals(
            42,
            $t->convertToDatabaseValue('42.01', $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $t = new IntType;
        $p = new Property;

        $this->assertEquals(
            42,
            $t->convertToPHPValue('42.01', $p)
        );
    }

    public function testNullable()
    {
        $t = new IntType;
        $p = new Property;

        $this->assertSame(
            null,
            $t->convertToDatabaseValue(null, $p)
        );

        $p->setNullable(false);
        $this->assertSame(
            0,
            $t->convertToDatabaseValue(null, $p)
        );
    }
}
