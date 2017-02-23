<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\UpdatePersister,
    PersisterInterface,
    Entity\DataExtractor,
    Entity\ChangesetComputer,
    Entity\Container,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Metadatas,
    Event\EntityAboutToBeUpdated,
    Event\EntityUpdated
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    ResultInterface,
    Query\Parameter
};
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Collection,
    CollectionInterface
};
use PHPUnit\Framework\TestCase;

class UpdatePersisterTest extends TestCase
{
    private $m;
    private $arClass;
    private $rClass;

    public function setUp()
    {
        $ar = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $this->arClass = get_class($ar);
        $r = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $this->rClass  = get_class($r);

        $this->m = new Metadatas;
        $this->m
            ->register(
                (new Aggregate(
                    new ClassName($this->arClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    ['Label']
                ))
                    ->withProperty('created', new DateType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            new Collection(['nullable' => null])
                        )
                    )
                    ->withChild(
                        (new ValueObject(
                            new ClassName('foo'),
                            ['AnotherLabel'],
                            (new ValueObjectRelationship(
                                new ClassName('foo'),
                                new RelationshipType('FOO'),
                                'rel',
                                'child'
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
                    )
            )
            ->register(
                (new Relationship(
                    new ClassName($this->rClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    new RelationshipType('type'),
                    new RelationshipEdge('start', Uuid::class, 'uuid'),
                    new RelationshipEdge('end', Uuid::class, 'uuid')
                ))
                    ->withProperty('created', new DateType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            new Collection(['nullable' => null])
                        )
                    )
            );
    }

    public function testPersist()
    {
        $p = new UpdatePersister(
            $changeset = new ChangesetComputer,
            $bus = $this->createMock(EventBusInterface::class),
            $extractor = new DataExtractor($this->m),
            $this->m
        );

        $this->assertInstanceOf(PersisterInterface::class, $p);

        $container = new Container;
        $conn = $this->createMock(ConnectionInterface::class);
        $aggregate = new $this->arClass;
        $rel = new class {
            public $created;
            public $empty;
            public $child;
        };
        $child = new class {
            public $content;
            public $empty;
        };
        $aggregate->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $aggregate->created = new \DateTimeImmutable('2016-01-01');
        $aggregate->rel = $rel;
        $rel->created = new \DateTimeImmutable('2016-01-01');
        $rel->child = $child;
        $child->content = 'foo';
        $container->push($aggregate->uuid, $aggregate, Container::STATE_MANAGED);
        $relationship = new $this->rClass;
        $relationship->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111112');
        $relationship->created = new \DateTimeImmutable('2016-01-01');
        $relationship->start = new Uuid($s = '11111111-1111-1111-1111-111111111113');
        $relationship->end = new Uuid($e = '11111111-1111-1111-1111-111111111114');
        $container->push($relationship->uuid, $relationship, Container::STATE_MANAGED);
        $count = $preCount = $postCount = 0;
        $changeset
            ->use(
                $aggregate->uuid,
                new Collection([
                    'created' => new \DateTimeImmutable('2015-01-01'),
                    'empty' => null,
                    'rel' => new Collection([
                        'created' => new \DateTimeImmutable('2015-01-01'),
                        'empty' => null,
                        'child' => new Collection([
                            'content' => 'bar',
                            'empty' => null,
                        ]),
                    ]),
                ])
            )
            ->use(
                $relationship->uuid,
                new Collection([
                    'created' => new \DateTimeImmutable('2015-01-01'),
                    'empty' => null,
                ])
            );

        $conn
            ->method('execute')
            ->will($this->returnCallback(function($query) use (&$count) {
                $this->assertSame(
                    'MATCH (e38c6cbd28bf165070d070980dd1fb595:Label { uuid: {e38c6cbd28bf165070d070980dd1fb595_identity} }), (e38c6cbd28bf165070d070980dd1fb595)-[e38c6cbd28bf165070d070980dd1fb595_rel:FOO]-(e38c6cbd28bf165070d070980dd1fb595_rel_child:AnotherLabel), ()-[e50ead852f3361489a400ab5c70f6c5cf:type { uuid: {e50ead852f3361489a400ab5c70f6c5cf_identity} }]-() SET e38c6cbd28bf165070d070980dd1fb595 += {e38c6cbd28bf165070d070980dd1fb595_props}, e38c6cbd28bf165070d070980dd1fb595_rel += {e38c6cbd28bf165070d070980dd1fb595_rel_props}, e38c6cbd28bf165070d070980dd1fb595_rel_child += {e38c6cbd28bf165070d070980dd1fb595_rel_child_props}, e50ead852f3361489a400ab5c70f6c5cf += {e50ead852f3361489a400ab5c70f6c5cf_props}',
                    $query->cypher()
                );
                $this->assertSame(6, $query->parameters()->count());
                $query
                    ->parameters()
                    ->each(function(int $idx, Parameter $value) {
                        $keys = [
                            'e38c6cbd28bf165070d070980dd1fb595_identity' => '11111111-1111-1111-1111-111111111111',
                            'e38c6cbd28bf165070d070980dd1fb595_props' => [
                                'created' => '2016-01-01T00:00:00+0000',
                            ],
                            'e38c6cbd28bf165070d070980dd1fb595_rel_props' => [
                                'created' => '2016-01-01T00:00:00+0000',
                            ],
                            'e38c6cbd28bf165070d070980dd1fb595_rel_child_props' => [
                                'content' => 'foo',
                            ],
                            'e50ead852f3361489a400ab5c70f6c5cf_identity' => '11111111-1111-1111-1111-111111111112',
                            'e50ead852f3361489a400ab5c70f6c5cf_props' => [
                                'created' => '2016-01-01T00:00:00+0000',
                            ],
                        ];

                        $this->assertTrue(isset($keys[$value->key()]));
                        $this->assertSame(
                            $keys[$value->key()],
                            $value->value()
                        );
                    });
                ++$count;

                return $this->createMock(ResultInterface::class);
            }));
        $bus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->callback(function(EntityAboutToBeUpdated $event) use ($aggregate): bool {
                return $event->entity() instanceof $aggregate &&
                    $event->identity() === $aggregate->uuid;
            }));
        $bus
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->callback(function(EntityAboutToBeUpdated $event) use ($relationship): bool {
                return $event->entity() instanceof $relationship &&
                    $event->identity() === $relationship->uuid;
            }));
        $bus
            ->expects($this->at(2))
            ->method('dispatch')
            ->with($this->callback(function(EntityUpdated $event) use ($aggregate): bool {
                return $event->entity() instanceof $aggregate &&
                    $event->identity() === $aggregate->uuid;
            }));
        $bus
            ->expects($this->at(3))
            ->method('dispatch')
            ->with($this->callback(function(EntityUpdated $event) use ($relationship): bool {
                return $event->entity() instanceof $relationship &&
                    $event->identity() === $relationship->uuid;
            }));

        $this->assertSame(null, $p->persist($conn, $container));
        $this->assertSame(1, $count);
        $this->assertSame(
            Container::STATE_MANAGED,
            $container->stateFor($aggregate->uuid)
        );
        $this->assertSame(
            Container::STATE_MANAGED,
            $container->stateFor($relationship->uuid)
        );
        $this->assertSame(
            0,
            $changeset
                ->compute(
                    $aggregate->uuid,
                    $extractor->extract($aggregate)
                )
                ->count()
        );
        $this->assertSame(
            0,
            $changeset
                ->compute(
                    $relationship->uuid,
                    $extractor->extract($relationship)
                )
                ->count()
        );
    }
}
