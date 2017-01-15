<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\BooleanType,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};

class BooleanTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            BooleanType::fromConfig(new Collection([]))
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            BooleanType::fromConfig(new Collection([]))
                ->isNullable()
        );
        $this->assertTrue(
            BooleanType::fromConfig(new Collection([
                'nullable' => null,
            ]))
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
        $t = BooleanType::fromConfig(new Collection([]));

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
            BooleanType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = BooleanType::fromConfig(new Collection([]));

        $this->assertSame(true, $t->fromDatabase(true));
        $this->assertSame(false, $t->fromDatabase(null));

        $this->assertSame(
            false,
            BooleanType::fromConfig(new Collection(['nullable' => null]))
                ->fromDatabase(null)
        );
    }
}
