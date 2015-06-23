<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\StringType;
use Innmind\Neo4j\ONM\Mapping\Property;

class StringTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $t = new StringType;
        $p = new Property;

        $this->assertEquals(
            '42',
            $t->convertToDatabaseValue(42, $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $t = new StringType;
        $p = new Property;

        $this->assertEquals(
            '42.01',
            $t->convertToPHPValue(42.01, $p)
        );
    }
}
