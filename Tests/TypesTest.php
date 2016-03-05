<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

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
use Innmind\Immutable\{
    Set,
    Collection
};

class TypesTest extends \PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $t = new Types;

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
    public function testThrowWhenAddingInvalidType()
    {
        (new Types)->add('stdClass');
    }

    public function testBuild()
    {
        $t = new Types;

        $this->assertInstanceOf(
            StringType::class,
            $t->build('string', new Collection([]))
        );
        $this->assertInstanceOf(
            ArrayType::class,
            $t->build('array', new Collection(['inner' => 'string']))
        );
    }
}
