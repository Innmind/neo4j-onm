<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\IntType,
    Type,
};
use PHPUnit\Framework\TestCase;

class IntTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new IntType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new IntType)->isNullable()
        );
        $this->assertTrue(
            IntType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $type = new IntType;

        $this->assertSame(42, $type->forDatabase(42.0));
        $this->assertSame(0, $type->forDatabase(null));

        $this->assertSame(
            null,
            IntType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = new IntType;

        $this->assertSame(42, $type->fromDatabase('42.0'));
        $this->assertSame(0, $type->fromDatabase(null));

        $this->assertSame(
            0,
            IntType::nullable()->fromDatabase(null)
        );
    }
}
