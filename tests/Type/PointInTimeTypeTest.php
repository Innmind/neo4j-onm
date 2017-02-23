<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\PointInTimeType,
    TypeInterface
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
    PointInTime\Earth\Now,
    Format\RSS
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};
use PHPUnit\Framework\TestCase;

class PointInTimeTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            PointInTimeType::fromConfig(new Collection([]))
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            PointInTimeType::fromConfig(new Collection([]))
                ->isNullable()
        );
        $this->assertTrue(
            PointInTimeType::fromConfig(new Collection([
                'nullable' => null,
            ]))
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
        $type = PointInTimeType::fromConfig(new Collection([]));

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
            PointInTimeType::fromConfig(new Collection([
                'format' => RSS::class
            ]))
                ->forDatabase(new PointInTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            PointInTimeType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = PointInTimeType::fromConfig(new Collection([]));

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
        PointInTimeType::fromConfig(new Collection([]))
            ->forDatabase(42);
    }
}
