<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\DateType,
    Type,
    Exception\InvalidArgumentException,
};
use PHPUnit\Framework\TestCase;

class DateTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new DateType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new DateType)->isNullable()
        );
        $this->assertTrue(
            DateType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $expected = '/2016-01-01T00:00:00\+\d{4}/';
        $type = new DateType;

        $this->assertRegExp(
            $expected,
            $type->forDatabase('2016-01-01')
        );
        $this->assertRegExp(
            $expected,
            $type->forDatabase(new \DateTime('2016-01-01'))
        );
        $this->assertRegExp(
            $expected,
            $type->forDatabase(new \DateTimeImmutable('2016-01-01'))
        );
        $this->assertRegExp(
            $expected,
            DateType::mutable()->forDatabase(new \DateTimeImmutable('2016-01-01'))
        );

        $this->assertSame(
            '160101',
            (new DateType('ymd'))->forDatabase(new \DateTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            DateType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = new DateType;

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $type->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            '160101',
            $type->fromDatabase('2016-01-01T00:00:00+0200')->format('ymd')
        );

        $this->assertSame(
            '01/01/2016',
            (new DateType('ymd'))->fromDatabase('160101')->format('d/m/Y')
        );

        $this->assertInstanceOf(
            \DateTime::class,
            DateType::mutable()->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            '01/01/2016',
            DateType::mutable('ymd')->fromDatabase('160101')->format('d/m/Y')
        );
    }

    public function testThrowWhenInvalidDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value "42" must be an instance of DateTimeInterface');

        (new DateType)->forDatabase(42);
    }
}
