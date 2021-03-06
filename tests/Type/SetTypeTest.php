<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\SetType,
    Type\StringType,
    Type,
    Exception\RecursiveTypeDeclaration,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class SetTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new SetType(new StringType, 'string')
        );
    }

    public function testThrowWhenInnerTypeIsArray()
    {
        $this->expectException(RecursiveTypeDeclaration::class);

        new SetType(new SetType(new StringType, 'string'), 'string');
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new SetType(new StringType, 'string'))->isNullable()
        );
        $this->assertTrue(
            SetType::nullable(new StringType, 'string')->isNullable()
        );
    }

    public function testForDatabase()
    {
        $type = new SetType(new StringType, 'string');

        $this->assertSame(
            ['foo'],
            $type->forDatabase((Set::of('string'))->add('foo'))
        );

        $this->assertSame(
            null,
            SetType::nullable(new StringType, 'string')->forDatabase(null)
        );
        $this->assertSame(
            [''],
            SetType::nullable(new StringType, 'string')->forDatabase((Set::of('string'))->add(''))
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
        $mockType = new class implements Type {
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
        };
        $type = new SetType($mockType, \get_class($mock));

        $this->assertSame(
            ['foo'],
            $type->forDatabase(
                Set::of(\get_class($mock), $mock)
            )
        );
    }

    public function testThrowWhenInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The set must be an instance of Set<string>');

        SetType::nullable(new StringType, 'string')->forDatabase(['']);
    }

    public function testThrowWhenInvalidSetType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The set must be an instance of Set<string>');

        SetType::nullable(new StringType, 'string')->forDatabase(Set::of('int'));
    }

    public function testFromDatabase()
    {
        $type = new SetType(new StringType, 'string');

        $this->assertInstanceOf(Set::class, $type->fromDatabase(['foo']));
        $this->assertSame('string', (string) $type->fromDatabase(['foo'])->type());
        $this->assertSame(['foo'], unwrap($type->fromDatabase(['foo'])));
        $this->assertInstanceOf(Set::class, $type->fromDatabase([null]));
        $this->assertSame('string', (string) $type->fromDatabase([null])->type());
        $this->assertSame([''], unwrap($type->fromDatabase([null])));

        $t = SetType::nullable(new StringType, 'string');

        $this->assertInstanceOf(Set::class, $type->fromDatabase([null]));
        $this->assertSame('string', (string) $type->fromDatabase([null])->type());
        $this->assertSame([''], unwrap($type->fromDatabase([null])));
    }

    public function testUseSpecificSetTypeInsteadOfInnerTypeName()
    {
        $type = SetType::nullable(new StringType, 'stdClass');

        $set = $type->fromDatabase([]);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('stdClass', (string) $set->type());
    }
}
