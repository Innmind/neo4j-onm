<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\BooleanType,
    Type,
    Types
};
use Innmind\Immutable\{
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class BooleanTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            BooleanType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            BooleanType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            BooleanType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, BooleanType::identifiers());
        $this->assertSame('string', (string) BooleanType::identifiers()->type());
        $this->assertSame(BooleanType::identifiers(), BooleanType::identifiers());
        $this->assertSame(['bool', 'boolean'], BooleanType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = BooleanType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(
            true,
            $t->forDatabase(
                new class {
                    public function __toString()
                    {
                        return 'foo';
                    }
                }
            )
        );
        $this->assertSame(false, $t->forDatabase(null));

        $this->assertSame(
            null,
            BooleanType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = BooleanType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(true, $t->fromDatabase(true));
        $this->assertSame(false, $t->fromDatabase(null));

        $this->assertSame(
            false,
            BooleanType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->fromDatabase(null)
        );
    }
}
