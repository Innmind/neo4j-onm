<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\ClassName,
    Metadata\Property,
    Type,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class ValueObjectTest extends TestCase
{
    public function testInterface()
    {
        $vo = ValueObject::of(
            $cn = new ClassName('foo'),
            Set::of('string', 'LabelA', 'LabelB'),
            $vor = ValueObjectRelationship::of(
                new ClassName('whatever'),
                new RelationshipType('whatever'),
                'foo',
                'bar'
            ),
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertSame($cn, $vo->class());
        $this->assertInstanceOf(SetInterface::class, $vo->labels());
        $this->assertSame('string', (string) $vo->labels()->type());
        $this->assertSame(['LabelA', 'LabelB'], $vo->labels()->toPrimitive());
        $this->assertSame($vor, $vo->relationship());
        $this->assertInstanceOf(MapInterface::class, $vo->properties());
        $this->assertSame('string', (string) $vo->properties()->keyType());
        $this->assertSame(Property::class, (string) $vo->properties()->valueType());
        $this->assertSame(1, $vo->properties()->count());
        $this->assertTrue($vo->properties()->contains('foo'));
    }
}
