<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\PointInTimeType,
    Type,
    Exception\InvalidArgumentException,
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
    PointInTime\Earth\Now,
    Format\RSS,
};
use PHPUnit\Framework\TestCase;

class PointInTimeTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new PointInTimeType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new PointInTimeType)->isNullable()
        );
        $this->assertTrue(
            PointInTimeType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $type = new PointInTimeType;

        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{2}:\d{2}/',
            $type->forDatabase(new PointInTime('2016-01-01'))
        );
        $this->assertRegExp(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}/',
            $type->forDatabase(new Now)
        );

        $this->assertRegExp(
            '/Fri, 01 Jan 2016 00:00:00 \+\d{4}/',
            (new PointInTimeType(new RSS))->forDatabase(new PointInTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            PointInTimeType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = new PointInTimeType;

        $this->assertInstanceOf(
            PointInTimeInterface::class,
            $type->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            'Fri, 01 Jan 2016 00:00:00 +0200',
            $type->fromDatabase('2016-01-01T00:00:00+0200')->format(new RSS)
        );
    }

    public function testThrowWhenInvalidDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value "42" must be an instance of PointInTimeInterface');

        (new PointInTimeType)->forDatabase(42);
    }
}
