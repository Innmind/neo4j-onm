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
        $t = new BooleanType;

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
            BooleanType::nullable()->forDatabase(null)
        );
    }

    public function testFromDatabase()
    {
        $t = new BooleanType;

        $this->assertSame(true, $t->fromDatabase(true));
        $this->assertSame(false, $t->fromDatabase(null));

        $this->assertSame(
            false,
            BooleanType::nullable()->fromDatabase(null)
        );
    }
}
