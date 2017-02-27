<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Types,
    TypeInterface,
    Type\ArrayType,
    Type\SetType,
    Type\BooleanType,
    Type\DateType,
    Type\FloatType,
    Type\IntType,
    Type\StringType
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testAll()
    {
        $t = new Types;

        $this->assertSame(
            'string',
            (string) $t->all()->keyType()
        );
        $this->assertSame(
            'string',
            (string) $t->all()->valueType()
        );
        $this->assertSame(
            [
                'array',
                'set',
                'bool',
                'boolean',
                'date',
                'datetime',
                'float',
                'int',
                'integer',
                'string',
            ],
            $t->all()->keys()->toPrimitive()
        );
        $this->assertSame(
            [
                ArrayType::class,
                SetType::class,
                BooleanType::class,
                BooleanType::class,
                DateType::class,
                DateType::class,
                FloatType::class,
                IntType::class,
                IntType::class,
                StringType::class,
            ],
            $t->all()->values()->toPrimitive()
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The type "stdClass" must implement TypeInterface
     */
    public function testThrowWhenRegisteringingInvalidType()
    {
        (new Types)->register('stdClass');
    }

    public function testBuild()
    {
        $t = new Types;

        $this->assertInstanceOf(
            StringType::class,
            $t->build('string', new Map('string', 'mixed'))
        );
        $this->assertInstanceOf(
            ArrayType::class,
            $t->build(
                'array',
                (new Map('string', 'mixed'))
                    ->put('inner', 'string')
            )
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidConfigMap()
    {
        (new Types)->build('string', new Map('string', 'variable'));
    }
}
