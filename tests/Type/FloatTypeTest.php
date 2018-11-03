<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\FloatType,
    Type,
};
use PHPUnit\Framework\TestCase;

class FloatTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new FloatType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new FloatType)->isNullable()
        );
        $this->assertTrue(
            FloatType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $t = new FloatType;

        $this->assertSame(42.0, $t->forDatabase(42));
        $this->assertSame(0.0, $t->forDatabase(null));

        $this->assertSame(
            null,
            FloatType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = new FloatType;

        $this->assertSame(42.0, $t->fromDatabase('42.0'));
        $this->assertSame(0.0, $t->fromDatabase(null));

        $this->assertSame(
            0.0,
            FloatType::nullable()->fromDatabase(null)
        );
    }
}
