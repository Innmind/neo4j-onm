<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\RemovePersister,
    Persister,
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
    Identity\Uuid,
    Metadatas,
    Event\EntityAboutToBeRemoved,
    Event\EntityRemoved,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Result,
    Query\Parameter,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class RemovePersisterTest extends TestCase
{
    private $metadatas;
    private $aggregateRootClass;
    private $relationshipClass;

    public function setUp(): void
    {
        $aggregateRoot = new class {
            public $uuid;
            public $rel;
        };
        $this->aggregateRootClass = \get_class($aggregateRoot);
        $relationship = new class {
            public $uuid;
            public $start;
            public $end;
        };
        $this->relationshipClass  = \get_class($relationship);

        $this->metadatas = new Metadatas(
            Aggregate::of(
                new ClassName($this->aggregateRootClass),
                new Identity('uuid', 'foo'),
                Set::of('string', 'Label'),
                null,
                Set::of(
                    Child::class,
                    Child::of(
                        new ClassName('foo'),
                        Set::of('string', 'AnotherLabel'),
                        Child\Relationship::of(
                            new ClassName('foo'),
                            new RelationshipType('FOO'),
                            'rel',
                            'child'
                        )
                    )
                )
            ),
            Relationship::of(
                new ClassName($this->relationshipClass),
                new Identity('uuid', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'uuid'),
                new RelationshipEdge('end', Uuid::class, 'uuid')
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Persister::class,
            new RemovePersister(
                new ChangesetComputer,
                $this->createMock(EventBus::class),
                $this->metadatas
            )
        );
    }

    public function testPersist()
    {
        $persist = new RemovePersister(
            new ChangesetComputer,
            $bus = $this->createMock(EventBus::class),
            $this->metadatas
        );

        $container = new Container;
        $conn = $this->createMock(Connection::class);
        $aggregate = new $this->aggregateRootClass;
        $rel = new class {
            public $child;
        };
        $child = new class {
        };
        $aggregate->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $aggregate->rel = $rel;
        $rel->child = $child;
        $container->push($aggregate->uuid, $aggregate, State::toBeRemoved());
        $relationship = new $this->relationshipClass;
        $relationship->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111112');
        $relationship->start = new Uuid($s = '11111111-1111-1111-1111-111111111113');
        $relationship->end = new Uuid($e = '11111111-1111-1111-1111-111111111114');
        $container->push($relationship->uuid, $relationship, State::toBeRemoved());
        $count = 0;

        $conn
            ->method('execute')
            ->will($this->returnCallback(function($query) use (&$count) {
                $this->assertSame(
                    'MATCH ()-[e50ead852f3361489a400ab5c70f6c5cf:type { uuid: $e50ead852f3361489a400ab5c70f6c5cf_identity }]-(), (e38c6cbd28bf165070d070980dd1fb595:Label { uuid: $e38c6cbd28bf165070d070980dd1fb595_identity }), (e38c6cbd28bf165070d070980dd1fb595)-[e38c6cbd28bf165070d070980dd1fb595_rel:FOO]-(e38c6cbd28bf165070d070980dd1fb595_rel_child:AnotherLabel) DELETE e50ead852f3361489a400ab5c70f6c5cf, e38c6cbd28bf165070d070980dd1fb595, e38c6cbd28bf165070d070980dd1fb595_rel_child, e38c6cbd28bf165070d070980dd1fb595_rel',
                    $query->cypher()
                );
                $this->assertCount(2, $query->parameters());
                $query
                    ->parameters()
                    ->foreach(function(string $key, Parameter $value) {
                        $keys = [
                            'e38c6cbd28bf165070d070980dd1fb595_identity' => '11111111-1111-1111-1111-111111111111',
                            'e50ead852f3361489a400ab5c70f6c5cf_identity' => '11111111-1111-1111-1111-111111111112',
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
                [$this->callback(static function(EntityAboutToBeRemoved $event) use ($aggregate): bool {
                    return $event->entity() instanceof $aggregate &&
                        $event->identity() === $aggregate->uuid;
                })],
                [$this->callback(static function(EntityAboutToBeRemoved $event) use ($relationship): bool {
                    return $event->entity() instanceof $relationship &&
                        $event->identity() === $relationship->uuid;
                })],
                [$this->callback(static function(EntityRemoved $event) use ($aggregate): bool {
                    return $event->entity() instanceof $aggregate &&
                        $event->identity() === $aggregate->uuid;
                })],
                [$this->callback(static function(EntityRemoved $event) use ($relationship): bool {
                    return $event->entity() instanceof $relationship &&
                        $event->identity() === $relationship->uuid;
                })],
            );

        $this->assertNull($persist($conn, $container));
        $this->assertSame(1, $count);
        $this->assertSame(
            State::removed(),
            $container->stateFor($aggregate->uuid)
        );
        $this->assertSame(
            State::removed(),
            $container->stateFor($relationship->uuid)
        );
    }
}
