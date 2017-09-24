<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\DateType,
    Type,
    Types
};
use Innmind\Immutable\{
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class DateTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            DateType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            DateType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, DateType::identifiers());
        $this->assertSame('string', (string) DateType::identifiers()->type());
        $this->assertSame(DateType::identifiers(), DateType::identifiers());
        $this->assertSame(['date', 'datetime'], DateType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $expected = '/2016-01-01T00:00:00\+\d{4}/';
        $t = DateType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertRegExp(
            $expected,
            $t->forDatabase('2016-01-01')
        );
        $this->assertRegExp(
            $expected,
            $t->forDatabase(new \DateTime('2016-01-01'))
        );
        $this->assertRegExp(
            $expected,
            $t->forDatabase(new \DateTimeImmutable('2016-01-01'))
        );
        $this->assertRegExp(
            $expected,
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('immutable', false),
                new Types
            )
                ->forDatabase(new \DateTimeImmutable('2016-01-01'))
        );

        $this->assertSame(
            '160101',
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('format', 'ymd'),
                new Types
            )
                ->forDatabase(new \DateTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = DateType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $t->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            '160101',
            $t->fromDatabase('2016-01-01T00:00:00+0200')->format('ymd')
        );

        $this->assertSame(
            '01/01/2016',
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('format', 'ymd'),
                new Types
            )
                ->fromDatabase('160101')
                ->format('d/m/Y')
        );

        $this->assertInstanceOf(
            \DateTime::class,
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('immutable', false),
                new Types
            )
                ->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            '01/01/2016',
            DateType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('format', 'ymd')
                    ->put('immutable', false),
                new Types
            )
                ->fromDatabase('160101')
                ->format('d/m/Y')
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The value "42" must be an instance of DateTimeInterface
     */
    public function testThrowWhenInvalidDate()
    {
        DateType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        )
            ->forDatabase(42);
    }
}
