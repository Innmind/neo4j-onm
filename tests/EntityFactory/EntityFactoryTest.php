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
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Relationship,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Entity\Container,
    Type,
    Exception\TypeError,
};
use Innmind\Neo4j\DBAL\{
    Result\Result,
    Result as ResultInterface,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class EntityFactoryTest extends TestCase
{
    private $make;

    public function setUp(): void
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
                ('empty', StringType::nullable()),
            Set::of(
                Child::class,
                Child::of(
                    new ClassName(get_class($child)),
                    Set::of('string', 'AnotherLabel'),
                    Child\Relationship::of(
                        new ClassName(get_class($rel)),
                        new RelationshipType('CHILD1_OF'),
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
        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $relationship = Relationship::of(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'uuid'),
            new RelationshipEdge('end', Uuid::class, 'uuid'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
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
        $variables = Map::of('string', Entity::class)
            ('n', $aggregate)
            ('r', $relationship);

        $entities = ($this->make)(
            $result,
            $variables
        );

        $this->assertInstanceOf(
            Set::class,
            $entities
        );
        $this->assertCount(2, $entities);
        $entities = unwrap($entities);
        $this->assertInstanceOf(
            (string) $aggregate->class(),
            \current($entities)
        );
        \next($entities);
        $this->assertInstanceOf(
            (string) $relationship->class(),
            \current($entities)
        );
        \reset($entities);
        $aggregateRoot = \current($entities);
        $this->assertInstanceOf(Uuid::class, $aggregateRoot->uuid);
        $this->assertSame(
            '11111111-1111-1111-1111-111111111111',
            $aggregateRoot->uuid->value()
        );
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
            (string) $aggregate->children()->get('rel')->relationship()->class(),
            $aggregateRoot->rel
        );
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
            (string) $aggregate->children()->get('rel')->class(),
            $aggregateRoot->rel->child
        );
        $this->assertSame('foo', $aggregateRoot->rel->child->content);
        $this->assertNull($aggregateRoot->rel->child->empty);
        \next($entities);
        $rel = \current($entities);
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
            ($this->make)($result, $variables)->equals(
                ($this->make)($result, $variables),
            ),
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
        $relationship = Relationship::of(
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
        $variables = Map::of('string', Entity::class)
            ('n', $aggregate)
            ('r', $relationship);

        $entities = ($this->make)(
            $result,
            $variables
        );

        $this->assertCount(0, $entities);
    }

    public function testThrowWhenInvalidVariableMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, Innmind\Neo4j\ONM\Metadata\Entity>');

        ($this->make)(
            $this->createMock(ResultInterface::class),
            Map::of('string', 'object')
        );
    }
}
