<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Entity,
    Metadata\Aggregate\Child,
    Metadata\RelationshipType,
    Type,
    EntityFactory\AggregateFactory,
    Repository\Repository,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    public function testInterface()
    {
        $ar = Aggregate::of(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid', 'UUID'),
            Set::of('string', 'LabelA'),
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class)),
            Set::of(
                Child::class,
                $vo = Child::of(
                    new ClassName('whatever'),
                    Set::of('string', 'whatever'),
                    Child\Relationship::of(
                        new ClassName('whatever'),
                        new RelationshipType('whatever'),
                        'foo',
                        'bar'
                    )
                )
            )
        );

        $this->assertInstanceOf(Entity::class, $ar);
        $this->assertSame($cn, $ar->class());
        $this->assertSame($i, $ar->identity());
        $this->assertSame(Repository::class, (string) $ar->repository());
        $this->assertSame(AggregateFactory::class, (string) $ar->factory());
        $this->assertInstanceOf(SetInterface::class, $ar->labels());
        $this->assertSame('string', (string) $ar->labels()->type());
        $this->assertSame(['LabelA'], $ar->labels()->toPrimitive());
        $this->assertInstanceOf(MapInterface::class, $ar->children());
        $this->assertSame('string', (string) $ar->children()->keyType());
        $this->assertSame(Child::class, (string) $ar->children()->valueType());
        $this->assertCount(1, $ar->children());
        $this->assertSame($vo, $ar->children()->current());
        $this->assertCount(1, $ar->properties());
        $this->assertTrue($ar->properties()->contains('foo'));
    }
}

