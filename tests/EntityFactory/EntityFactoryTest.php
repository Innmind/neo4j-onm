<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\EntityFactory,
    Translation\ResultTranslator,
    Identity\Generators,
    Identity\Uuid,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Relationship,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Entity\Container,
    Types,
    Type,
};
use Innmind\Neo4j\DBAL\{
    Result\Result,
    Result as ResultInterface,
};
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};
use PHPUnit\Framework\TestCase;

class EntityFactoryTest extends TestCase
{
    private $make;

    public function setUp()
    {
        $this->make = new EntityFactory(
            new ResultTranslator,
            $generators = new Generators,
            new Resolver(
                new RelationshipFactory($generators)
            ),
            new Container
        );
    }

    public function testMake()
    {
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
        $aggregate = Aggregate::of(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            Set::of('string', 'Label'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )),
            Set::of(
                ValueObject::class,
                ValueObject::of(
                    new ClassName(get_class($child)),
                    Set::of('string', 'AnotherLabel'),
                    ValueObjectRelationship::of(
                        new ClassName(get_class($rel)),
                        new RelationshipType('CHILD1_OF'),
                        'rel',
                        'child'
                    )
                        ->withProperty('created', new DateType)
                        ->withProperty(
                            'empty',
                            StringType::fromConfig(
                                (new Map('string', 'mixed'))
                                    ->put('nullable', null),
                                new Types
                            )
                        ),
                    Map::of('string', Type::class)
                        ('content', new StringType)
                        ('empty', StringType::fromConfig(
                            (new Map('string', 'mixed'))
                                ->put('nullable', null),
                            new Types
                        ))
                )
            )
        );
        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $relationship = new Relationship(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'uuid'),
            new RelationshipEdge('end', Uuid::class, 'uuid')
        );
        $relationship = $relationship
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )
            );
        $result = Result::fromRaw([
            'columns' => ['n', 'r'],
            'data' => [[
                'row' => [
                    [
                        'uuid' => '11111111-1111-1111-1111-111111111111',
                        'created' => '2016-01-01T00:00:00+0200',
                    ],
                    [
                        'uuid' => '11111111-1111-1111-1111-111111111112',
                        'created' => '2016-01-03T00:00:00+0200',
                    ],
                ],
                'graph' => [
                    'nodes' => [
                        [
                            'id' => 1,
                            'labels' => ['Node'],
                            'properties' => [
                                'uuid' => '11111111-1111-1111-1111-111111111111',
                                'created' => '2016-01-01T00:00:00+0200',
                            ],
                        ],
                        [
                            'id' => 2,
                            'labels' => ['Child'],
                            'properties' => [
                                'content' => 'foo',
                            ],
                        ],
                        [
                            'id' => 3,
                            'labels' => ['Child'],
                            'properties' => [
                                'content' => 'bar',
                            ],
                        ],
                        [
                            'id' => 4,
                            'labels' => ['Child2'],
                            'properties' => [
                                'content' => 'baz',
                            ],
                        ],
                        [
                            'id' => 5,
                            'labels' => ['Node'],
                            'properties' => [
                                'uuid' => '66666666-6666-6666-6666-666666666666',
                                'created' => '2016-01-01T00:00:00+0200',
                            ],
                        ],
                        [
                            'id' => 6,
                            'labels' => ['Child'],
                            'properties' => [
                                'uuid' => '24242424-2424-2424-2424-242424242424',
                                'created' => '2016-01-02T00:00:00+0200',
                            ],
                        ],
                    ],
                    'relationships' => [
                        [
                            'id' => 1,
                            'type' => 'CHILD1_OF',
                            'startNode' => 2,
                            'endNode' => 1,
                            'properties' => [
                                'created' => '2016-01-01T00:00:00+0200',
                            ],
                        ],
                        [
                            'id' => 3,
                            'type' => 'CHILD2_OF',
                            'startNode' => 4,
                            'endNode' => 1,
                            'properties' => [
                                'created' => '2016-01-03T00:00:00+0200',
                            ],
                        ],
                        [
                            'id' => 4,
                            'type' => 'CHILD1_OF',
                            'startNode' => 6,
                            'endNode' => 5,
                            'properties' => [
                                'uuid' => '11111111-1111-1111-1111-111111111112',
                                'created' => '2016-01-03T00:00:00+0200',
                            ],
                        ],
                    ],
                ],
            ]],
        ]);
        $variables = (new Map('string', Entity::class))
            ->put('n', $aggregate)
            ->put('r', $relationship);

        $entities = ($this->make)(
            $result,
            $variables
        );

        $this->assertInstanceOf(
            SetInterface::class,
            $entities
        );
        $this->assertCount(2, $entities);
        $this->assertInstanceOf(
            (string) $aggregate->class(),
            $entities->current()
        );
        $entities->next();
        $this->assertInstanceOf(
            (string) $relationship->class(),
            $entities->current()
        );
        $entities->rewind();
        $ar = $entities->current();
        $this->assertInstanceOf(Uuid::class, $ar->uuid);
        $this->assertSame(
            '11111111-1111-1111-1111-111111111111',
            $ar->uuid->value()
        );
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->created->format('c')
        );
        $this->assertNull($ar->empty);
        $this->assertInstanceOf(
            (string) $aggregate->children()->get('rel')->relationship()->class(),
            $ar->rel
        );
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->rel->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->rel->created->format('c')
        );
        $this->assertNull($ar->rel->empty);
        $this->assertInstanceOf(
            (string) $aggregate->children()->get('rel')->class(),
            $ar->rel->child
        );
        $this->assertSame('foo', $ar->rel->child->content);
        $this->assertNull($ar->rel->child->empty);
        $entities->next();
        $rel = $entities->current();
        $this->assertInstanceOf((string) $relationship->class(), $rel);
        $this->assertInstanceOf(Uuid::class, $rel->uuid);
        $this->assertSame(
            '11111111-1111-1111-1111-111111111112',
            $rel->uuid->value()
        );
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $rel->created
        );
        $this->assertSame(
            '2016-01-03T00:00:00+02:00',
            $rel->created->format('c')
        );
        $this->assertNull($rel->empty);
        $this->assertInstanceOf(Uuid::class, $rel->start);
        $this->assertInstanceOf(Uuid::class, $rel->end);
        $this->assertSame(
            '24242424-2424-2424-2424-242424242424',
            $rel->start->value()
        );
        $this->assertSame(
            '66666666-6666-6666-6666-666666666666',
            $rel->end->value()
        );

        $this->assertTrue(
            ($this->make)($result, $variables)->equals($entities)
        );
    }

    public function testMakeWhenEntityNotFound()
    {
        $entity = new class {
            public $uuid;
        };
        $aggregate = Aggregate::of(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            Set::of('string', 'Label')
        );
        $entity = new class {
            public $uuid;
            public $start;
            public $end;
        };
        $relationship = new Relationship(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'uuid'),
            new RelationshipEdge('end', Uuid::class, 'uuid')
        );
        $result = Result::fromRaw([
            'columns' => [],
            'data' => [[
                'row' => [],
                'graph' => [
                    'nodes' => [],
                    'relationships' => [],
                ],
            ]],
        ]);
        $variables = (new Map('string', Entity::class))
            ->put('n', $aggregate)
            ->put('r', $relationship);

        $entities = ($this->make)(
            $result,
            $variables
        );

        $this->assertCount(0, $entities);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type MapInterface<string, Innmind\Neo4j\ONM\Metadata\Entity>
     */
    public function testThrowWhenInvalidVariableMap()
    {
        ($this->make)(
            $this->createMock(ResultInterface::class),
            new Map('string', 'object')
        );
    }
}
