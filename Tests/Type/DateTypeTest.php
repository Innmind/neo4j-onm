<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

use Innmind\Neo4j\ONM\{
    Type\DateType,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};

class DateTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            DateType::fromConfig(new Collection([]))
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            DateType::fromConfig(new Collection([]))
                ->isNullable()
        );
        $this->assertTrue(
            DateType::fromConfig(new Collection([
                'nullable' => null,
            ]))
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
        $t = DateType::fromConfig(new Collection([]));

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
            DateType::fromConfig(new Collection(['Immutable' => false]))
                ->forDatabase(new \DateTimeImmutable('2016-01-01'))
        );

        $this->assertSame(
            '160101',
            DateType::fromConfig(new Collection(['format' => 'ymd']))
                ->forDatabase(new \DateTime('2016-01-01'))
        );

        $this->assertSame(
            null,
            DateType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = DateType::fromConfig(new Collection([]));

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
            DateType::fromConfig(new Collection(['format' => 'ymd']))
                ->fromDatabase('160101')
                ->format('d/m/Y')
        );

        $this->assertInstanceOf(
            \DateTime::class,
            DateType::fromConfig(new Collection(['immutable' => false]))
                ->fromDatabase('2016-01-01T00:00:00+0200')
        );
        $this->assertSame(
            '01/01/2016',
            DateType::fromConfig(new Collection([
                'format' => 'ymd',
                'immutable' => false,
            ]))
                ->fromDatabase('160101')
                ->format('d/m/Y')
        );
    }
}
