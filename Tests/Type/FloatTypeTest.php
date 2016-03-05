<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

use Innmind\Neo4j\ONM\{
    Type\FloatType,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};

class FloatTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            FloatType::fromConfig(new Collection([]))
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
        $t = FloatType::fromConfig(new Collection([]));

        $this->assertSame(42.0, $t->forDatabase(42));
        $this->assertSame(0.0, $t->forDatabase(null));

        $this->assertSame(
            null,
            FloatType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = FloatType::fromConfig(new Collection([]));

        $this->assertSame(42.0, $t->fromDatabase('42.0'));
        $this->assertSame(0.0, $t->fromDatabase(null));

        $this->assertSame(
            0.0,
            FloatType::fromConfig(new Collection(['nullable' => null]))
                ->fromDatabase(null)
        );
    }
}
