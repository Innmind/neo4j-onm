<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Persister;

use Innmind\Neo4j\ONM\{
    Persister\RemovePersister,
    PersisterInterface,
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
    Metadata\EntityInterface,
    Identity\Uuid,
    Metadatas,
    Events,
    Event\RemoveEvent
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    ResultInterface,
    Query\Parameter
};
use Symfony\Component\EventDispatcher\EventDispatcher;

class RemovePersisterTest extends \PHPUnit_Framework_TestCase
{
    private $m;
    private $arClass;
    private $rClass;

    public function setUp()
    {
        $ar = new class {
            public $uuid;
            public $rel;
        };
        $this->arClass = get_class($ar);
        $r = new class {
            public $uuid;
            public $start;
            public $end;
        };
        $this->rClass  = get_class($r);

        $this->m = new Metadatas;
        $this->m
            ->add(
                (new Aggregate(
                    new ClassName($this->arClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    ['Label']
                ))
                    ->withChild(
                        new ValueObject(
                            new ClassName('foo'),
                            ['AnotherLabel'],
                            new ValueObjectRelationship(
                                new ClassName('foo'),
                                new RelationshipType('FOO'),
                                'rel',
                                'child'
                            )
                        )
                    )
            )
            ->add(
                new Relationship(
                    new ClassName($this->rClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    new RelationshipType('type'),
                    new RelationshipEdge('start', Uuid::class, 'uuid'),
                    new RelationshipEdge('end', Uuid::class, 'uuid')
                )
            );
    }

    public function testPersist()
    {
        $p = new RemovePersister(
            new ChangesetComputer,
            $d = new EventDispatcher,
            $this->m
        );

        $this->assertInstanceOf(PersisterInterface::class, $p);

        $container = new Container;
        $conn = $this->getMock(ConnectionInterface::class);
        $aggregate = new $this->arClass;
        $rel = new class {
            public $child;
        };
        $child = new class {};
        $aggregate->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $aggregate->rel = $rel;
        $rel->child = $child;
        $container->push($aggregate->uuid, $aggregate, Container::STATE_TO_BE_REMOVED);
        $relationship = new $this->rClass;
        $relationship->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111112');
        $relationship->start = new Uuid($s = '11111111-1111-1111-1111-111111111113');
        $relationship->end = new Uuid($e = '11111111-1111-1111-1111-111111111114');
        $container->push($relationship->uuid, $relationship, Container::STATE_TO_BE_REMOVED);
        $count = $preCount = $postCount = 0;

        $conn
            ->method('execute')
            ->will($this->returnCallback(function($query) use (&$count) {
                $this->assertSame(
                    'MATCH ()-[e50ead852f3361489a400ab5c70f6c5cf:type { uuid: {e50ead852f3361489a400ab5c70f6c5cf_identity} }]-(), (e38c6cbd28bf165070d070980dd1fb595:Label { uuid: {e38c6cbd28bf165070d070980dd1fb595_identity} }), (e38c6cbd28bf165070d070980dd1fb595)-[e38c6cbd28bf165070d070980dd1fb595_rel:FOO]-(e38c6cbd28bf165070d070980dd1fb595_rel_child:AnotherLabel) DELETE e50ead852f3361489a400ab5c70f6c5cf, e38c6cbd28bf165070d070980dd1fb595, e38c6cbd28bf165070d070980dd1fb595_rel_child, e38c6cbd28bf165070d070980dd1fb595_rel',
                    $query->cypher()
                );
                $this->assertSame(2, $query->parameters()->count());
                $query
                    ->parameters()
                    ->each(function(int $idx, Parameter $value) {
                        $keys = [
                            'e38c6cbd28bf165070d070980dd1fb595_identity' => '11111111-1111-1111-1111-111111111111',
                            'e50ead852f3361489a400ab5c70f6c5cf_identity' => '11111111-1111-1111-1111-111111111112',
                        ];

                        $this->assertTrue(isset($keys[$value->key()]));
                        $this->assertSame(
                            $keys[$value->key()],
                            $value->value()
                        );
                    });
                ++$count;

                return $this->getMock(ResultInterface::class);
            }));
        $d->addListener(
            Events::PRE_REMOVE,
            function(RemoveEvent $event) use (&$preCount, $aggregate, $relationship) {
                ++$preCount;
                $this->assertTrue(
                    $event->entity() instanceof $aggregate ||
                    $event->entity() instanceof $relationship
                );
                $this->assertTrue(
                    $event->identity() === $aggregate->uuid ||
                    $event->identity() === $relationship->uuid
                );
            }
        );
        $d->addListener(
            Events::POST_REMOVE,
            function(RemoveEvent $event) use (&$postCount, $aggregate, $relationship) {
                ++$postCount;
                $this->assertTrue(
                    $event->entity() instanceof $aggregate ||
                    $event->entity() instanceof $relationship
                );
                $this->assertTrue(
                    $event->identity() === $aggregate->uuid ||
                    $event->identity() === $relationship->uuid
                );
            }
        );

        $this->assertSame(null, $p->persist($conn, $container));
        $this->assertSame(1, $count);
        $this->assertSame(2, $preCount);
        $this->assertSame(2, $postCount);
        $this->assertSame(
            Container::STATE_REMOVED,
            $container->stateFor($aggregate->uuid)
        );
        $this->assertSame(
            Container::STATE_REMOVED,
            $container->stateFor($relationship->uuid)
        );
    }
}
