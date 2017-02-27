<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\SetType,
    TypeInterface,
    Types
};
use Innmind\Immutable\{
    SetInterface,
    Map,
    Set,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class SetTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            SetType::fromConfig(
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
        SetType::fromConfig(
            new Map('string', 'mixed'),
            new Types
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\RecursiveTypeDeclarationException
     */
    public function testThrowWhenInnerTypeIsArray()
    {
        SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'set'),
            new Types
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            SetType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('inner', 'string'),
                new Types
            )
                ->isNullable()
        );
        $this->assertTrue(
            SetType::fromConfig(
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
        $this->assertInstanceOf(SetInterface::class, SetType::identifiers());
        $this->assertSame('string', (string) SetType::identifiers()->type());
        $this->assertSame(SetType::identifiers(), SetType::identifiers());
        $this->assertSame(['set'], SetType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'string'),
            new Types
        );

        $this->assertSame(
            ['foo'],
            $t->forDatabase((new Set('string'))->add('foo'))
        );

        $this->assertSame(
            null,
            SetType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->forDatabase(null)
        );
        $this->assertSame(
            [''],
            SetType::fromConfig(
                (new Map('string', 'mixed'))
                    ->put('nullable', null)
                    ->put('inner', 'string'),
                new Types
            )
                ->forDatabase((new Set('string'))->add(''))
        );
    }

    public function testForDatabaseWithCastableInnerValue()
    {
        $mock = new class {
            public function __toString(): string
            {
                return 'foo';
            }
        };
        $mockType = new class implements TypeInterface {
            public static function fromConfig(MapInterface $c, Types $t): TypeInterface
            {
                return new self;
            }

            public function forDatabase($value)
            {
                return (string) $value;
            }

            public function fromDatabase($value)
            {
            }

            public function isNullable(): bool
            {
                return false;
            }

            public static function identifiers(): SetInterface
            {
                return (new Set('string'))->add('mock');
            }
        };
        $type = SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'mock')
                ->put('set_type', get_class($mock)),
            new Types(get_class($mockType))
        );

        $this->assertSame(
            ['foo'],
            $type->forDatabase(
                (new Set(get_class($mock)))->add($mock)
            )
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The set must be an instance of SetInterface<string>
     */
    public function testThrowWhenInvalidType()
    {
        SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('nullable', null)
                ->put('inner', 'string'),
            new Types
        )
            ->forDatabase(['']);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The set must be an instance of SetInterface<string>
     */
    public function testThrowWhenInvalidSetType()
    {
        SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('nullable', null)
                ->put('inner', 'string'),
            new Types
        )
            ->forDatabase(new Set('int'));
    }

    public function testFromDatabase()
    {
        $t = SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('inner', 'string'),
            new Types
        );

        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase(['foo']));
        $this->assertSame('string', (string) $t->fromDatabase(['foo'])->type());
        $this->assertSame(['foo'], $t->fromDatabase(['foo'])->toPrimitive());
        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase([null]));
        $this->assertSame('string', (string) $t->fromDatabase([null])->type());
        $this->assertSame([''], $t->fromDatabase([null])->toPrimitive());

        $t = SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('nullable', null)
                ->put('inner', 'string'),
            new Types
        );

        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase([null]));
        $this->assertSame('string', (string) $t->fromDatabase([null])->type());
        $this->assertSame([''], $t->fromDatabase([null])->toPrimitive());
    }

    public function testUseSpecificSetTypeInsteadOfInnerTypeName()
    {
        $type = SetType::fromConfig(
            (new Map('string', 'mixed'))
                ->put('nullable', null)
                ->put('inner', 'string')
                ->put('set_type', 'stdClass'),
            new Types
        );

        $set = $type->fromDatabase([]);

        $this->assertInstanceOf(SetInterface::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
    }
}
