<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\PointInTimeType,
    TypeInterface,
    Types
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
    PointInTime\Earth\Now,
    Format\RSS
};
use Innmind\Immutable\{
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class PointInTimeTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            PointInTimeType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            PointInTimeType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            PointInTimeType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, PointInTimeType::identifiers());
        $this->assertSame('string', (string) PointInTimeType::identifiers()->type());
        $this->assertSame(PointInTimeType::identifiers(), PointInTimeType::identifiers());
        $this->assertSame(['point_in_time'], PointInTimeType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $type = PointInTimeType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

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
            PointInTimeType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('format', RSS::class),
                new Types
            )
                ->forDatabase(new PointInTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            PointInTimeType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = PointInTimeType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertInstanceOf(
            PointInTimeInterface::class,
            $type->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            'Fri, 01 Jan 2016 00:00:00 +0200',
            $type->fromDatabase('2016-01-01T00:00:00+0200')->format(new RSS)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The value "42" must be an instance of PointInTimeInterface
     */
    public function testThrowWhenInvalidDate()
    {
        PointInTimeType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        )
            ->forDatabase(42);
    }
}
