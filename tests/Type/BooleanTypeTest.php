<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\BooleanType,
    Type,
};
use PHPUnit\Framework\TestCase;

class BooleanTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new BooleanType
        );
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new BooleanType)->isNullable()
        );
        $this->assertTrue(
            BooleanType::nullable()->isNullable()
        );
    }

    public function testForDatabase()
    {
        $type = new BooleanType;

        $this->assertSame(
            true,
            $type->forDatabase(
                new class {
                    public function __toString()
                    {
                        return 'foo';
                    }
                }
            )
        );
        $this->assertSame(false, $type->forDatabase(null));

        $this->assertSame(
            null,
            BooleanType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $type = new BooleanType;

        $this->assertSame(true, $type->fromDatabase(true));
        $this->assertSame(false, $type->fromDatabase(null));

        $this->assertSame(
            false,
            BooleanType::nullable()->fromDatabase(null)
        );
    }
}
