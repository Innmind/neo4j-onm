<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type\ArrayType,
    Type\StringType,
    Type,
    Exception\RecursiveTypeDeclaration,
};
use PHPUnit\Framework\TestCase;

class ArrayTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new ArrayType(new StringType)
        );
    }

    public function testThrowWhenInnerTypeIsArray()
    {
        $this->expectException(RecursiveTypeDeclaration::class);

        new ArrayType(new ArrayType(new StringType));
    }

    public function testIsNullable()
    {
        $this->assertFalse(
            (new ArrayType(new StringType))->isNullable()
        );
        $this->assertTrue(
            ArrayType::nullable(new StringType)->isNullable()
        );
    }

    public function testForDatabase()
    {
        $t = new ArrayType(new StringType);

        $this->assertSame(
            ['foo'],
            $t->forDatabase(['foo'])
        );
        $this->assertSame([''], $t->forDatabase([null]));

        $this->assertSame(
            null,
            ArrayType::nullable(new StringType)->forDatabase(null)
        );
        $this->assertSame(
            [null],
            ArrayType::nullable(StringType::nullable())->forDatabase([null])
        );
    }

    public function testFromDatabase()
    {
        $t = new ArrayType(new StringType);

        $this->assertSame(['foo'], $t->fromDatabase(['foo']));
        $this->assertSame([''], $t->fromDatabase([null]));

        $this->assertSame(
            [''],
            ArrayType::nullable(new StringType)->fromDatabase([null])
        );
    }
}
