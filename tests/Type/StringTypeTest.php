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
        $type = new StringType;

        $this->assertSame(
            'foo',
            $type->forDatabase(
                new class {
                    public function __toString()
                    {
                        return 'foo';
                    }
                }
            )
        );
        $this->assertSame('', $type->forDatabase(null));

        $this->assertSame(
            null,
            StringType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = new StringType;

        $this->assertSame('foo', $type->fromDatabase('foo'));
        $this->assertSame('', $type->fromDatabase(null));

        $this->assertSame(
            '',
            StringType::nullable()->fromDatabase(null)
        );
    }
}
