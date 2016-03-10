<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\{
    EntityFactory,
    Translation\ResultTranslator,
    Identity\Generators,
    Identity\Uuid,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    EntityFactory\AggregateRootFactory,
    Metadata\AggregateRoot,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Relationship,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Entity\Container
};
use Innmind\Neo4j\DBAL\Result;
use Innmind\Immutable\{
    Collection,
    Map,
    SetInterface
};

class EntityFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $f;

    public function setUp()
    {
        $this->f = new EntityFactory(
            new ResultTranslator,
            $g = new Generators,
            (new Resolver)
                ->add(new RelationshipFactory($g)),
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
        $aggregate = new AggregateRoot(
            new ClassName(get_class($entity)),
            new Identity('uuid', Uuid::class),
            new Repository('foo'),
            new Factory(AggregateRootFactory::class),
            new Alias('foo'),
            ['Label']
        );
        $aggregate = $aggregate
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    new Collection(['nullable' => null])
                )
            )
            ->withChild(
                (new ValueObject(
                    new ClassName(get_class($child)),
                    ['AnotherLabel'],
                    (new ValueObjectRelationship(
                        new ClassName(get_class($rel)),
                        new RelationshipType('CHILD1_OF'),
                        'rel',
                        'child',
                        true
                    ))
                        ->withProperty('created', new DateType)
                        ->withProperty(
                            'empty',
                            StringType::fromConfig(
                                new Collection(['nullable' => null])
                            )
                        )
                ))
                    ->withProperty('content', new StringType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            new Collection(['nullable' => null])
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
            new Repository('foo'),
            new Factory(RelationshipFactory::class),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'uuid'),
            new RelationshipEdge('end', Uuid::class, 'uuid')
        );
        $relationship = $relationship
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    new Collection(['nullable' => null])
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
                            'id' => 2,
                            'type' => 'CHILD1_OF',
                            'startNode' => 3,
                            'endNode' => 1,
                            'properties' => [
                                'created' => '2016-01-02T00:00:00+0200',
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
        $variables = (new Map('string', EntityInterface::class))
            ->put('n', $aggregate)
            ->put('r', $relationship);

        $entities = $this->f->make(
            $result,
            $variables
        );

        $this->assertInstanceOf(
            SetInterface::class,
            $entities
        );
        $this->assertSame(2, $entities->size());
        $this->assertInstanceOf(
            (string) $aggregate->class(),
            $entities->toPrimitive()[0]
        );
        $this->assertInstanceOf(
            (string) $relationship->class(),
            $entities->toPrimitive()[1]
        );
        $ar = $entities->toPrimitive()[0];
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
        $this->assertSame(null, $ar->empty);
        $this->assertInstanceOf(SetInterface::class, $ar->rel);
        $this->assertSame(
            (string) $aggregate->children()->get('rel')->relationship()->class(),
            (string) $ar->rel->type()
        );
        $this->assertSame(2, $ar->rel->size());
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->rel->toPrimitive()[0]->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->rel->toPrimitive()[0]->created->format('c')
        );
        $this->assertSame(null, $ar->rel->toPrimitive()[0]->empty);
        $this->assertInstanceOf(
            (string) $aggregate->children()->get('rel')->class(),
            $ar->rel->toPrimitive()[0]->child
        );
        $this->assertSame('foo', $ar->rel->toPrimitive()[0]->child->content);
        $this->assertSame(null, $ar->rel->toPrimitive()[0]->child->empty);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->rel->toPrimitive()[1]->created
        );
        $this->assertSame(
            '2016-01-02T00:00:00+02:00',
            $ar->rel->toPrimitive()[1]->created->format('c')
        );
        $this->assertSame(null, $ar->rel->toPrimitive()[1]->empty);
        $this->assertInstanceOf(
            (string) $aggregate->children()->get('rel')->class(),
            $ar->rel->toPrimitive()[1]->child
        );
        $this->assertSame('bar', $ar->rel->toPrimitive()[1]->child->content);
        $this->assertSame(null, $ar->rel->toPrimitive()[1]->child->empty);
        $rel = $entities->toPrimitive()[1];
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
        $this->assertSame(null, $rel->empty);
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
            $this->f->make($result, $variables)->equals($entities)
        );
    }
}
