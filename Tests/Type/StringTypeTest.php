<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

use Innmind\Neo4j\ONM\{
    Type\StringType,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    Collection
};

class StringTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            StringType::fromConfig(new Collection([]))
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            StringType::fromConfig(new Collection([]))
                ->isNullable()
        );
        $this->assertTrue(
            StringType::fromConfig(new Collection([
                'nullable' => null,
            ]))
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
        $t = StringType::fromConfig(new Collection([]));

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
            StringType::fromConfig(new Collection(['nullable' => null]))
                ->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = StringType::fromConfig(new Collection([]));

        $this->assertSame('foo', $t->fromDatabase('foo'));
        $this->assertSame('', $t->fromDatabase(null));

        $this->assertSame(
            '',
            StringType::fromConfig(new Collection(['nullable' => null]))
                ->fromDatabase(null)
        );
    }
}
