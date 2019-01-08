<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\AggregateFactory,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Identity as IdentityInterface,
    Type,
    EntityFactory,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
    Stream,
};
use PHPUnit\Framework\TestCase;

class AggregateFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityFactory::class,
            new AggregateFactory
        );
    }

    public function testMake()
    {
        $make = new AggregateFactory;

        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $rel = new class {
            public $created;
            public $empty;
            public $child;
        };
        $child = new class {
            public $content;
            public $empty;
        };
        $meta = Aggregate::of(
            new ClassName(get_class($entity)),
            new Identity('uuid', 'foo'),
            Set::of('string', 'Label'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable()),
            Set::of(
                Child::class,
                Child::of(
                    new ClassName(get_class($child)),
                    Set::of('string', 'AnotherLabel'),
                    Child\Relationship::of(
                        new ClassName(get_class($rel)),
                        new RelationshipType('foo'),
                        'rel',
                        'child',
                        Map::of('string', Type::class)
                            ('created', new DateType)
                            ('empty', StringType::nullable())
                    ),
                    Map::of('string', Type::class)
                        ('content', new StringType)
                        ('empty', StringType::nullable())
                )
            )
        );

        $aggregateRoot = $make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            Map::of('string', 'mixed')
                ('uuid', 24)
                ('created', '2016-01-01T00:00:00+0200')
                ('rel', Map::of('string', 'mixed')
                    ('created', '2016-01-01T00:00:00+0200')
                    ('child', Map::of('string', 'mixed')
                        ('content', 'foo')
                    )
                )
        );

        $this->assertInstanceOf(get_class($entity), $aggregateRoot);
        $this->assertSame($identity, $aggregateRoot->uuid);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $aggregateRoot->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $aggregateRoot->created->format('c')
        );
        $this->assertNull($aggregateRoot->empty);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $aggregateRoot->rel->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $aggregateRoot->rel->created->format('c')
        );
        $this->assertNull($aggregateRoot->rel->empty);
        $this->assertInstanceOf(
            get_class($child),
            $aggregateRoot->rel->child
        );
        $this->assertSame('foo', $aggregateRoot->rel->child->content);
        $this->assertNull($aggregateRoot->rel->child->empty);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildNonAggregate()
    {
        (new AggregateFactory)(
            $this->createMock(IdentityInterface::class),
            $this->createMock(Entity::class),
            new Map('string', 'mixed')
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 3 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenTryingToBuildWithInvalidData()
    {
        (new AggregateFactory)(
            $this->createMock(IdentityInterface::class),
            Aggregate::of(
                new ClassName('foo'),
                new Identity('uuid', 'foo'),
                Set::of('string', 'Label')
            ),
            new Map('string', 'variable')
        );
    }
}
