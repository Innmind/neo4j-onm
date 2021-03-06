<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\UpdatePersister,
    Persister,
    Entity\DataExtractor\DataExtractor,
    Entity\ChangesetComputer,
    Entity\Container,
    Entity\Container\State,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Metadatas,
    Type,
    Event\EntityAboutToBeUpdated,
    Event\EntityUpdated,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Result,
    Query\Parameter,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class UpdatePersisterTest extends TestCase
{
    private $metadatas;
    private $aggregateRootClass;
    private $relationshipClass;

    public function setUp(): void
    {
        $aggregateRoot = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $this->aggregateRootClass = \get_class($aggregateRoot);
        $relationship = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $this->relationshipClass  = \get_class($relationship);

        $this->metadatas = new Metadatas(
            Aggregate::of(
                new ClassName($this->aggregateRootClass),
                new Identity('uuid', 'foo'),
                Set::of('string', 'Label'),
                Map::of('string', Type::class)
                    ('created', new DateType)
                    ('empty', StringType::nullable()),
                Set::of(
                    Child::class,
                    Child::of(
                        new ClassName('foo'),
                        Set::of('string', 'AnotherLabel'),
                        Child\Relationship::of(
                            new ClassName('foo'),
                            new RelationshipType('FOO'),
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
            ),
            Relationship::of(
                new ClassName($this->relationshipClass),
                new Identity('uuid', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'uuid'),
                new RelationshipEdge('end', Uuid::class, 'uuid'),
                Map::of('string', Type::class)
                    ('created', new DateType)
                    ('empty', StringType::nullable())
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Persister::class,
            new UpdatePersister(
                new ChangesetComputer,
                $this->createMock(EventBus::class),
                new DataExtractor($this->metadatas),
                $this->metadatas
            )
        );
    }

    public function testPersist()
    {
        $persist = new UpdatePersister(
            $changeset = new ChangesetComputer,
            $bus = $this->createMock(EventBus::class),
            $extract = new DataExtractor($this->metadatas),
            $this->metadatas
        );

        $container = new Container;
        $conn = $this->createMock(Connection::class);
        $aggregate = new $this->aggregateRootClass;
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
        $container->push($aggregate->uuid, $aggregate, State::managed());
        $relationship = new $this->relationshipClass;
        $relationship->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111112');
        $relationship->created = new \DateTimeImmutable('2016-01-01');
        $relationship->start = new Uuid($s = '11111111-1111-1111-1111-111111111113');
        $relationship->end = new Uuid($e = '11111111-1111-1111-1111-111111111114');
        $container->push($relationship->uuid, $relationship, State::managed());
        $count = 0;
        $changeset->use(
            $aggregate->uuid,
            Map::of('string', 'mixed')
                ('created', new \DateTimeImmutable('2015-01-01'))
                ('empty', null)
                ('rel', Map::of('string', 'mixed')
                    ('created', new \DateTimeImmutable('2015-01-01'))
                    ('empty', null)
                    ('child', Map::of('string', 'mixed')
                        ('content', 'bar')
                        ('empty', null)
                    )
                )
        );
        $changeset->use(
            $relationship->uuid,
            Map::of('string', 'mixed')
                ('created', new \DateTimeImmutable('2015-01-01'))
                ('empty', null)
        );

        $conn
            ->method('execute')
            ->will($this->returnCallback(function($query) use (&$count) {
                $this->assertSame(
                    'MATCH (e38c6cbd28bf165070d070980dd1fb595:Label { uuid: $e38c6cbd28bf165070d070980dd1fb595_identity }), (e38c6cbd28bf165070d070980dd1fb595)-[e38c6cbd28bf165070d070980dd1fb595_rel:FOO]-(e38c6cbd28bf165070d070980dd1fb595_rel_child:AnotherLabel), ()-[e50ead852f3361489a400ab5c70f6c5cf:type { uuid: $e50ead852f3361489a400ab5c70f6c5cf_identity }]-() SET e38c6cbd28bf165070d070980dd1fb595 += $e38c6cbd28bf165070d070980dd1fb595_props, e38c6cbd28bf165070d070980dd1fb595_rel += $e38c6cbd28bf165070d070980dd1fb595_rel_props, e38c6cbd28bf165070d070980dd1fb595_rel_child += $e38c6cbd28bf165070d070980dd1fb595_rel_child_props, e50ead852f3361489a400ab5c70f6c5cf += $e50ead852f3361489a400ab5c70f6c5cf_props',
                    $query->cypher()
                );
                $this->assertCount(6, $query->parameters());
                $query
                    ->parameters()
                    ->foreach(function(string $key, Parameter $value) {
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

                        $this->assertTrue(isset($keys[$key]));
                        $this->assertSame(
                            $keys[$key],
                            $value->value()
                        );
                    });
                ++$count;

                return $this->createMock(Result::class);
            }));
        $bus
            ->expects($this->exactly(4))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function(EntityAboutToBeUpdated $event) use ($aggregate): bool {
                    return $event->entity() instanceof $aggregate &&
                        $event->identity() === $aggregate->uuid;
                })],
                [$this->callback(static function(EntityAboutToBeUpdated $event) use ($relationship): bool {
                    return $event->entity() instanceof $relationship &&
                        $event->identity() === $relationship->uuid;
                })],
                [$this->callback(static function(EntityUpdated $event) use ($aggregate): bool {
                    return $event->entity() instanceof $aggregate &&
                        $event->identity() === $aggregate->uuid;
                })],
                [$this->callback(static function(EntityUpdated $event) use ($relationship): bool {
                    return $event->entity() instanceof $relationship &&
                        $event->identity() === $relationship->uuid;
                })],
            );

        $this->assertNull($persist($conn, $container));
        $this->assertSame(1, $count);
        $this->assertSame(
            State::managed(),
            $container->stateFor($aggregate->uuid)
        );
        $this->assertSame(
            State::managed(),
            $container->stateFor($relationship->uuid)
        );
        $this->assertCount(
            0,
            $changeset->compute(
                $aggregate->uuid,
                $extract($aggregate)
            )
        );
        $this->assertCount(
            0,
            $changeset->compute(
                $relationship->uuid,
                $extract($relationship)
            )
        );
    }
}
