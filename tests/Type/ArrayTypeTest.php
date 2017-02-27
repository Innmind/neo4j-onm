<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\ArrayType,
    TypeInterface,
    Types
};
use Innmind\Immutable\{
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class ArrayTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('inner', 'string'),
                new Types
            )
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\TypeDeclarationException
     * @expectedExceptionMessage Missing config key "inner" in type declaration
     */
    public function testThrowWhenMissingInnerType()
    {
        ArrayType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\RecursiveTypeDeclarationException
     */
    public function testThrowWhenInnerTypeIsArray()
    {
        ArrayType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'array'),
            new Types
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('inner', 'string'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->isNullable()
        );
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, ArrayType::identifiers());
        $this->assertSame('string', (string) ArrayType::identifiers()->type());
        $this->assertSame(ArrayType::identifiers(), ArrayType::identifiers());
        $this->assertSame(['array'], ArrayType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = ArrayType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'string'),
            new Types
        );

        $this->assertSame(
            ['foo'],
            $t->forDatabase(['foo'])
        );
        $this->assertSame([''], $t->forDatabase([null]));

        $this->assertSame(
            null,
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->forDatabase(null)
        );
        $this->assertSame(
            [null],
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->forDatabase([null])
        );
    }

    public function testFromDatabase()
    {
        $t = ArrayType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'string'),
            new Types
        );

        $this->assertSame(['foo'], $t->fromDatabase(['foo']));
        $this->assertSame([''], $t->fromDatabase([null]));

        $this->assertSame(
            [''],
            ArrayType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->fromDatabase([null])
        );
    }
}
