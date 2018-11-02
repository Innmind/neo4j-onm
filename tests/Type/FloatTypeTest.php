<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\FloatType,
    Type,
    Types,
};
use Innmind\Immutable\{
    SetInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class FloatTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            FloatType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            FloatType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            FloatType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, FloatType::identifiers());
        $this->assertSame('string', (string) FloatType::identifiers()->type());
        $this->assertSame(FloatType::identifiers(), FloatType::identifiers());
        $this->assertSame(['float'], FloatType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = FloatType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(42.0, $t->forDatabase(42));
        $this->assertSame(0.0, $t->forDatabase(null));

        $this->assertSame(
            null,
            FloatType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = FloatType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(42.0, $t->fromDatabase('42.0'));
        $this->assertSame(0.0, $t->fromDatabase(null));

        $this->assertSame(
            0.0,
            FloatType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->fromDatabase(null)
        );
    }
}
