<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\StringType,
    Type,
};
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new StringType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new StringType)->isNullable()
        );
        $this->assertTrue(
            StringType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $t = new StringType;

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
            StringType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = new StringType;

        $this->assertSame('foo', $t->fromDatabase('foo'));
        $this->assertSame('', $t->fromDatabase(null));

        $this->assertSame(
            '',
            StringType::nullable()->fromDatabase(null)
        );
    }
}
