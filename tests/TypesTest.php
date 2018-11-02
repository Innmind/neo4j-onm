<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Types,
    Type,
    Type\ArrayType,
    Type\SetType,
    Type\BooleanType,
    Type\DateType,
    Type\FloatType,
    Type\IntType,
    Type\StringType,
    Type\PointInTimeType,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testAll()
    {
        $build = new Types;

        $defaults = [
            'array' => ArrayType::class,
            'set' => SetType::class,
            'bool' => BooleanType::class,
            'boolean' => BooleanType::class,
            'date' => DateType::class,
            'datetime' => DateType::class,
            'float' => FloatType::class,
            'int' => IntType::class,
            'integer' => IntType::class,
            'string' => StringType::class,
        ];

        foreach ($defaults as $key => $value) {
            $this->assertInstanceOf($value, $build(
                $key,
                (new Map('string', 'mixed'))
                    ->put('inner', 'string')
            ));
        }
    }

    public function testRegisterCustomType()
    {
        $build = new Types(PointInTimeType::class);

        $this->assertInstanceOf(
            PointInTimeType::class,
            $build(
                'point_in_time',
                new Map('string', 'mixed')
            )
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     * @expectedExceptionMessage The type "stdClass" must implement Type
     */
    public function testThrowWhenRegisteringingInvalidType()
    {
        new Types('stdClass');
    }

    public function testBuild()
    {
        $build = new Types;

        $this->assertInstanceOf(
            StringType::class,
            $build('string', new Map('string', 'mixed'))
        );
        $this->assertInstanceOf(
            ArrayType::class,
            $build(
                'array',
                (new Map('string', 'mixed'))
                    ->put('inner', 'string')
            )
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenInvalidConfigMap()
    {
        (new Types)('string', new Map('string', 'variable'));
    }
}
