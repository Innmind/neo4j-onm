<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    UnitOfWork,
    Entity\Container,
    EntityFactory,
    Translation\ResultTranslator,
    Identity\Generators,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    EntityFactory\AggregateFactory,
    Metadatas,
    Translation\IdentityMatchTranslator,
    Persister\DelegationPersister,
    Persister\InsertPersister,
    Persister\UpdatePersister,
    Persister\RemovePersister,
    PersisterInterface,
    Entity\ChangesetComputer,
    Entity\DataExtractor,
    Identity\Uuid,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\EntityInterface,
    Exception\IdentityNotManagedException
};
use Innmind\Neo4j\DBAL\{
    ConnectionFactory,
    Query
};
use Innmind\Immutable\{
    Set,
    SetInterface,
    Map
};
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
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
            getenv('CI') ? 'localhost' : 'docker',
            'http'
        )
            ->for('neo4j', 'ci')
            ->build();
        $this->container = new Container;
        $this->entityFactory = new EntityFactory(
            new ResultTranslator,
            $this->generators = new Generators,
            (new Resolver)
                ->register(new RelationshipFactory($this->generators)),
            $this->container
        );
        $this->metadatas = new Metadatas;
        $changeset = new ChangesetComputer;
        $extractor = new DataExtractor($this->metadatas);
        $dispatcher = new EventDispatcher;

        $this
            ->metadatas
            ->register(
                new Aggregate(
                    new ClassName($this->aggregateClass),
                    new Identity('uuid', Uuid::class),
                    new Repository('foo'),
                    new Factory(AggregateFactory::class),
                    new Alias('foo'),
                    ['Label']
                )
            );

        $this->uow = new UnitOfWork(
            $this->conn,
            $this->container,
            $this->entityFactory,
            new IdentityMatchTranslator,
            $this->metadatas,
            new DelegationPersister(
                (new Set(PersisterInterface::class))
                    ->add(
                        new InsertPersister(
                            $changeset,
                            $dispatcher,
                            $extractor,
                            $this->metadatas
                        )
                    )
                    ->add(
                        new UpdatePersister(
                            $changeset,
                            $dispatcher,
                            $extractor,
                            $this->metadatas
                        )
                    )
                    ->add(
                        new RemovePersister(
                            $changeset,
                            $dispatcher,
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
            (new Map('string', EntityInterface::class))
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

        $this->setExpectedException(IdentityNotManagedException::class);
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
        $this->setExpectedException(IdentityNotManagedException::class);
        $uow->stateFor($entity->uuid);
    }

    public function testDetachUnmanagedEntity()
    {
        $entity = new $this->aggregateClass;
        $entity->uuid = new Uuid('11111111-1111-1111-1111-111111111111');

        $this->assertSame($this->uow, $this->uow->detach($entity));
    }
}
