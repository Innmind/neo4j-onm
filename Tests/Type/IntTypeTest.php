<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

use Innmind\Neo4j\ONM\{
    Type\IntType,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};

class IntTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            IntType::fromConfig(new Collection([]))
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            IntType::fromConfig(new Collection([]))
                ->isNullable()
        );
        $this->assertTrue(
            IntType::fromConfig(new Collection([
                'nullable' => null,
            ]))
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
        $t = IntType::fromConfig(new Collection([]));

        $this->assertSame(42, $t->forDatabase(42.0));
        $this->assertSame(0, $t->forDatabase(null));

        $this->assertSame(
            null,
            IntType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = IntType::fromConfig(new Collection([]));

        $this->assertSame(42, $t->fromDatabase('42.0'));
        $this->assertSame(0, $t->fromDatabase(null));

        $this->assertSame(
            0,
            IntType::fromConfig(new Collection(['nullable' => null]))
                ->fromDatabase(null)
        );
    }
}
