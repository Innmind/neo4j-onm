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
    MapInterface,
};
use PHPUnit\Framework\TestCase;

class ValueObjectTest extends TestCase
{
    public function testInterface()
    {
        $vo = new ValueObject(
            $cn = new ClassName('foo'),
            ['LabelA', 'LabelB'],
            $vor = new ValueObjectRelationship(
                new ClassName('whatever'),
                new RelationshipType('whatever'),
                'foo',
                'bar'
            )
        );

        $this->assertSame($cn, $vo->class());
        $this->assertInstanceOf(SetInterface::class, $vo->labels());
        $this->assertSame('string', (string) $vo->labels()->type());
        $this->assertSame(['LabelA', 'LabelB'], $vo->labels()->toPrimitive());
        $this->assertSame($vor, $vo->relationship());
        $this->assertInstanceOf(MapInterface::class, $vo->properties());
        $this->assertSame('string', (string) $vo->properties()->keyType());
        $this->assertSame(Property::class, (string) $vo->properties()->valueType());
        $this->assertSame(0, $vo->properties()->count());

        $vo2 = $vo->withProperty('foo', $this->createMock(Type::class));

        $this->assertNotSame($vo, $vo2);
        $this->assertInstanceOf(ValueObject::class, $vo2);
        $this->assertSame(0, $vo->properties()->count());
        $this->assertSame(1, $vo2->properties()->count());
        $this->assertTrue($vo2->properties()->contains('foo'));
    }
}
