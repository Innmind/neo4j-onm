<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\BooleanType;
use Innmind\Neo4j\ONM\Mapping\Property;

class BooleanTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $t = new BooleanType;
        $p = new Property;

        $this->assertEquals(
            true,
            $t->convertToDatabaseValue(1, $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $t = new BooleanType;
        $p = new Property;

        $this->assertEquals(
            true,
            $t->convertToPHPValue(1, $p)
        );
    }

    public function testNullable()
    {
        $t = new BooleanType;
        $p = new Property;

        $this->assertEquals(
            null,
            $t->convertToDatabaseValue(null, $p)
        );

        $p->setNullable(false);
        $this->assertSame(
            false,
            $t->convertToDatabaseValue(null, $p)
        );
    }
}
