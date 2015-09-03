<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\DateType;
use Innmind\Neo4j\ONM\Mapping\Property;

class DateTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $t = new DateType;
        $p = new Property;
        $d = new \DateTime('2015-05-15T00:00:00+0000');

        $this->assertEquals(
            '2015-05-15T00:00:00+0000',
            $t->convertToDatabaseValue($d, $p)
        );
        $this->assertEquals(
            '2015-05-15T00:00:00+0000',
            $t->convertToDatabaseValue('2015-05-15T00:00:00+0000', $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $t = new DateType;
        $p = new Property;

        $this->assertEquals(
            new \DateTime('2015-05-15T00:00:00+0000'),
            $t->convertToPHPValue('2015-05-15T00:00:00+0000', $p)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowWhenInvalidPHPValue()
    {
        $t = new DateType;
        $p = new Property;

        $t->convertToDatabaseValue([], $p);
    }

    public function testNullable()
    {
        $t = new DateType;
        $p = new Property;

        $this->assertSame(
            null,
            $t->convertToDatabaseValue(null, $p)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowIfNotNUllableAndNotADate()
    {
        $t = new DateType;
        $p = new Property;
        $p->setNullable(false);

        $t->convertToDatabaseValue(null, $p);
    }
}
