<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\StringType,
    Type,
    Types,
};
use Innmind\Immutable\{
    SetInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            StringType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            StringType::fromConfig(
                new Map('string', 'mixed'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            StringType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, StringType::identifiers());
        $this->assertSame('string', (string) StringType::identifiers()->type());
        $this->assertSame(StringType::identifiers(), StringType::identifiers());
        $this->assertSame(['string'], StringType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = StringType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame(
            'foo',
            $t->forDatabase(
                new class {
                    public function __toString()
                    {
                        return 'foo';
                    }
                }
            )
        );
        $this->assertSame('', $t->forDatabase(null));

        $this->assertSame(
            null,
            StringType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = StringType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );

        $this->assertSame('foo', $t->fromDatabase('foo'));
        $this->assertSame('', $t->fromDatabase(null));

        $this->assertSame(
            '',
            StringType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null),
                new Types
            )
                ->fromDatabase(null)
        );
    }
}
