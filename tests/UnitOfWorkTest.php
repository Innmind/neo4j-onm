<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    UnitOfWork,
    Entity\Container,
    EntityFactory\EntityFactory,
    Translation\ResultTranslator,
    Identity\Generators,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    EntityFactory\AggregateFactory,
    Metadatas,
    Translation\IdentityMatch\DelegationTranslator as IdentityMatchTranslator,
    Persister\DelegationPersister,
    Persister\InsertPersister,
    Persister\UpdatePersister,
    Persister\RemovePersister,
    Persister,
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Identity\Uuid,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\Entity,
    Exception\IdentityNotManagedException
};
use Innmind\Neo4j\DBAL\{
    ConnectionFactory,
    Query\Query
};
use Innmind\EventBus\EventBusInterface;
use Innmind\HttpTransport\GuzzleTransport;
use Innmind\Http\{
    Translator\Response\Psr7Translator,
    Factory\Header\Factories
};
use Innmind\Immutable\{
    Stream,
    SetInterface,
    Map
};
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class UnitOfWorkTest extends TestCase
{
    private $uow;
    private $aggregateClass;
    private $conn;
    private $container;
    private $entityFactory;
    private $metadatas;
    private $generators;

    public function setUp()
    {
        $entity = new class {
            public $uuid;
        };
        $this->aggregateClass = get_class($entity);

        $this->conn = ConnectionFactory::on(
            'localhost',
            'http'
        )
            ->for('neo4j', 'ci')
            ->useTransport(
                new GuzzleTransport(
                    new Client,
                    new Psr7Translator(Factories::default())
                )
            )
            ->build();
        $this->container = new Container;
        $this->entityFactory = new EntityFactory(
            new ResultTranslator,
            $this->generators = new Generators,
            new Resolver(
                new RelationshipFactory($this->generators)
            ),
            $this->container
        );
        $this->metadatas = new Metadatas(
            new Aggregate(
                new ClassName($this->aggregateClass),
                new Identity('uuid', Uuid::class),
                new Repository('foo'),
                new Factory(AggregateFactory::class),
                new Alias('foo'),
                ['Label']
            )
        );
        $changeset = new ChangesetComputer;
        $extractor = new DataExtractor($this->metadatas);
        $eventBus = $this->createMock(EventBusInterface::class);

        $this->uow = new UnitOfWork(
            $this->conn,
            $this->container,
            $this->entityFactory,
            new IdentityMatchTranslator,
            $this->metadatas,
            new DelegationPersister(
                (new Stream(Persister::class))
                    ->add(
                        new InsertPersister(
                            $changeset,
                            $eventBus,
                            $extractor,
                            $this->metadatas
                        )
                    )
                    ->add(
                        new UpdatePersister(
                            $changeset,
                            $eventBus,
                            $extractor,
                            $this->metadatas
                        )
                    )
                    ->add(
                        new RemovePersister(
                            $changeset,
                            $eventBus,
                            $this->metadatas
                        )
                    )
            ),
            $this->generators
        );
    }

    public function testConnection()
    {
        $this->assertSame(
            $this->conn,
            $this->uow->connection()
        );
    }

    public function testPersist()
    {
        $entity = new $this->aggregateClass;
        $entity->uuid = new Uuid('11111111-1111-1111-1111-111111111111');

        $this->assertFalse($this->uow->contains($entity->uuid));
        $this->assertSame(
            $this->uow,
            $this->uow->persist($entity)
        );
        $this->assertTrue($this->uow->contains($entity->uuid));
        $this->assertSame(
            Container::STATE_NEW,
            $this->uow->stateFor($entity->uuid)
        );
        $this->assertTrue(
            $this->generators->get(Uuid::class)->knows($entity->uuid->value())
        );

        return [$this->uow, $entity];
    }

    /**
     * @depends testPersist
     */
    public function testCommit(array $args)
    {
        list($uow, $entity) = $args;

        $this->assertSame(
            $uow,
            $uow->commit()
        );
        $this->assertSame(
            Container::STATE_MANAGED,
            $uow->stateFor($entity->uuid)
        );

        return $args;
    }

    /**
     * @depends testCommit
     */
    public function testGet(array $args)
    {
        list($uow, $expectedEntity) = $args;
        $expectedUuid = $expectedEntity->uuid;

        $entity = $uow->get(
            $this->aggregateClass,
            new Uuid('11111111-1111-1111-1111-111111111111')
        );
        $this->assertSame($expectedEntity, $entity);
        $this->assertSame($expectedUuid, $entity->uuid);
    }

    public function testLoadEntityFromDatabase()
    {
        $this->conn->execute(
            (new Query)
                ->create('n', ['Label'])
                ->withProperty('uuid', '{uuid}')
                ->withParameter('uuid', $uuid = '11111111-1111-1111-1111-111111111112')
        );

        $entity = $this->uow->get(
            $this->aggregateClass,
            $identity = new Uuid($uuid)
        );

        $this->assertInstanceOf($this->aggregateClass, $entity);
        $this->assertSame($identity, $entity->uuid);
        $this->conn->execute(
            (new Query)
                ->match('(n {uuid:"11111111-1111-1111-1111-111111111112"})')
                ->delete('n')
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\EntityNotFoundException
     */
    public function testThrowWhenTheEntityIsNotFound()
    {
        $this->uow->get(
            $this->aggregateClass,
            new Uuid('11111111-1111-1111-1111-111111111112')
        );
    }

    /**
     * @depends testCommit
     */
    public function testExecute(array $args)
    {
        list($uow, $expectedEntity) = $args;

        $data = $uow->execute(
            (new Query)
                ->match('entity', ['Label'])
                ->withProperty('uuid', '"11111111-1111-1111-1111-111111111111"')
                ->return('entity'),
            (new Map('string', Entity::class))
                ->put('entity', $this->metadatas->get($this->aggregateClass))
        );

        $this->assertInstanceOf(SetInterface::class, $data);
        $this->assertSame(1, $data->size());
        $this->assertSame($expectedEntity, $data->current());
    }

    public function testRemoveNewEntity()
    {
        $entity = new $this->aggregateClass;
        $entity->uuid = new Uuid('11111111-1111-1111-1111-111111111111');

        $this->uow->persist($entity);
        $this->assertSame($this->uow, $this->uow->remove($entity));
        $this->assertSame(
            Container::STATE_REMOVED,
            $this->uow->stateFor($entity->uuid)
        );
    }

    /**
     * @depends testCommit
     */
    public function testRemoveManagedEntity(array $args)
    {
        list($uow, $entity) = $args;

        $this->assertSame(
            $uow,
            $uow->remove($entity)
        );
        $this->assertSame(
            Container::STATE_TO_BE_REMOVED,
            $uow->stateFor($entity->uuid)
        );
        $uow->commit();
    }

    public function testRemoveUnmanagedEntity()
    {
        $entity = new $this->aggregateClass;
        $entity->uuid = new Uuid('11111111-1111-1111-1111-111111111111');

        $this->assertSame($this->uow, $this->uow->remove($entity));

        $this->expectException(IdentityNotManagedException::class);
        $this->uow->stateFor($entity->uuid);
    }

    /**
     * @depends testCommit
     */
    public function testDetach(array $args)
    {
        list($uow, $entity) = $args;

        $this->assertSame(
            $uow,
            $uow->detach($entity)
        );
        $this->assertFalse($uow->contains($entity->uuid));
        $this->expectException(IdentityNotManagedException::class);
        $uow->stateFor($entity->uuid);
    }

    public function testDetachUnmanagedEntity()
    {
        $entity = new $this->aggregateClass;
        $entity->uuid = new Uuid('11111111-1111-1111-1111-111111111111');

        $this->assertSame($this->uow, $this->uow->detach($entity));
    }
}
