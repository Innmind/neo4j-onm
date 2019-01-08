<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata\Aggregate;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate\Child,
    Metadata\Aggregate\Child\Relationship,
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

class ChildTest extends TestCase
{
    public function testInterface()
    {
        $valueObject = Child::of(
            $className = new ClassName('foo'),
            Set::of('string', 'LabelA', 'LabelB'),
            $valueObjectRelationship = Relationship::of(
                new ClassName('whatever'),
                new RelationshipType('whatever'),
                'foo',
                'bar'
            ),
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertSame($className, $valueObject->class());
        $this->assertInstanceOf(SetInterface::class, $valueObject->labels());
        $this->assertSame('string', (string) $valueObject->labels()->type());
        $this->assertSame(['LabelA', 'LabelB'], $valueObject->labels()->toPrimitive());
        $this->assertSame($valueObjectRelationship, $valueObject->relationship());
        $this->assertInstanceOf(MapInterface::class, $valueObject->properties());
        $this->assertSame('string', (string) $valueObject->properties()->keyType());
        $this->assertSame(Property::class, (string) $valueObject->properties()->valueType());
        $this->assertSame(1, $valueObject->properties()->count());
        $this->assertTrue($valueObject->properties()->contains('foo'));
    }
}
