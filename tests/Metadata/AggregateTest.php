<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\Entity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type,
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface,
};
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    public function testInterface()
    {
        $ar = new Aggregate(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid', 'UUID'),
            $r = new Repository('Class'),
            $f = new Factory('AnotherClass'),
            $a = new Alias('CanBeClassName'),
            ['LabelA']
        );

        $this->assertInstanceOf(Entity::class, $ar);
        $this->assertSame($cn, $ar->class());
        $this->assertSame($i, $ar->identity());
        $this->assertSame($r, $ar->repository());
        $this->assertSame($f, $ar->factory());
        $this->assertSame($a, $ar->alias());
        $this->assertInstanceOf(SetInterface::class, $ar->labels());
        $this->assertSame('string', (string) $ar->labels()->type());
        $this->assertSame(['LabelA'], $ar->labels()->toPrimitive());
        $this->assertInstanceOf(MapInterface::class, $ar->children());
        $this->assertSame('string', (string) $ar->children()->keyType());
        $this->assertSame(ValueObject::class, (string) $ar->children()->valueType());
        $this->assertCount(0, $ar->children());

        $ar2 = $ar->withChild(
            $vo = new ValueObject(
                new ClassName('whatever'),
                ['whatever'],
                new ValueObjectRelationship(
                    new ClassName('whatever'),
                    new RelationshipType('whatever'),
                    'foo',
                    'bar'
                )
            )
        );

        $this->assertNotSame($ar, $ar2);
        $this->assertInstanceOf(Aggregate::class, $ar2);
        $this->assertCount(0, $ar->children());
        $this->assertCount(1, $ar2->children());
        $this->assertSame($vo, $ar2->children()->current());

        $ar2 = $ar->withProperty(
            'foo',
            $this->createMock(Type::class)
        );
        $this->assertNotSame($ar, $ar2);
        $this->assertCount(0, $ar->properties());
        $this->assertCount(1, $ar2->properties());
        $this->assertTrue($ar2->properties()->contains('foo'));
    }
}

