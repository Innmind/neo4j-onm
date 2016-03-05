<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

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
