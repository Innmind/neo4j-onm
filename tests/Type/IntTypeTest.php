<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\IntType,
    Type,
    Types,
};
use Innmind\Immutable\{
    SetInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class IntTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            IntType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            IntType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            IntType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, IntType::identifiers());
        $this->assertSame('string', (string) IntType::identifiers()->type());
        $this->assertSame(IntType::identifiers(), IntType::identifiers());
        $this->assertSame(['int', 'integer'], IntType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = IntType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(42, $t->forDatabase(42.0));
        $this->assertSame(0, $t->forDatabase(null));

        $this->assertSame(
            null,
            IntType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = IntType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(42, $t->fromDatabase('42.0'));
        $this->assertSame(0, $t->fromDatabase(null));

        $this->assertSame(
            0,
            IntType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->fromDatabase(null)
        );
    }
}
