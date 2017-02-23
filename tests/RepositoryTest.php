<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Repository,
    RepositoryInterface,
    UnitOfWork,
    Entity\Container,
    EntityFactory,
    Translation\ResultTranslator,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
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
    Metadata\Repository as MetaRepository,
    Metadata\Factory,
    Metadata\Alias,
    Type\StringType,
    Exception\EntityNotFoundException
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\ConnectionFactory;
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Set,
    SetInterface,
    Collection
};
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private $r;
    private $class;
    private $uow;

    public function setUp()
    {
        $entity = new class {
            public $uuid;
            public $content;
        };
        $this->class = get_class($entity);

        $conn = ConnectionFactory::on(
            'localhost',
            'http'
        )
            ->for('neo4j', 'ci')
            ->build();
        $container = new Container;
        $entityFactory = new EntityFactory(
            new ResultTranslator,
            $generators = new Generators,
            (new Resolver)
                ->register(new RelationshipFactory($generators)),
            $container
        );
        $metadatas = new Metadatas;
        $changeset = new ChangesetComputer;
        $extractor = new DataExtractor($metadatas);
        $eventBus = $this->createMock(EventBusInterface::class);

        $metadatas->register(
            $meta = (new Aggregate(
                new ClassName($this->class),
                new Identity('uuid', Uuid::class),
                new MetaRepository(Repository::class),
                new Factory(AggregateFactory::class),
                new Alias('foo'),
                ['Label']
            ))
                ->withProperty('content', StringType::fromConfig(new Collection([
                    'nullable' => true,
                ])))
        );

        $uow = new UnitOfWork(
            $conn,
            $container,
            $entityFactory,
            new IdentityMatchTranslator,
            $metadatas,
            new DelegationPersister(
                (new Set(PersisterInterface::class))
                    ->add(
                        new InsertPersister(
                            $changeset,
                            $eventBus,
                            $extractor,
                            $metadatas
                        )
                    )
                    ->add(
                        new UpdatePersister(
                            $changeset,
                            $eventBus,
                            $extractor,
                            $metadatas
                        )
                    )
                    ->add(
                        new RemovePersister(
                            $changeset,
                            $eventBus,
                            $metadatas
                        )
                    )
            ),
            $generators
        );

        $this->r = new Repository(
            $this->uow = $uow,
            new MatchTranslator,
            new SpecificationTranslator,
            $meta
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(RepositoryInterface::class, $this->r);

        $entity = new $this->class;
        $entity->uuid = new Uuid('21111111-1111-1111-1111-111111111111');

        $this->assertFalse($this->r->has($entity->uuid));
        $this->assertSame($this->r, $this->r->add($entity));
        $this->assertTrue($this->r->has($entity->uuid));
        $this->assertSame(
            $entity,
            $this->r->get($entity->uuid)
        );
        $this->assertSame($this->r, $this->r->remove($entity));
        $this->assertFalse($this->r->has($entity->uuid));

        $this->expectException(EntityNotFoundException::class);
        $this->r->get($entity->uuid);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\EntityNotFoundException
     */
    public function testThrowWhenGettingUnknownEntity()
    {
        $this->r->get(new Uuid('24111111-1111-1111-1111-111111111111'));
    }

    public function testDoesntFind()
    {
        $this->assertSame(
            null,
            $this->r->find(new Uuid('24111111-1111-1111-1111-111111111111'))
        );
    }

    public function testAll()
    {
        $entity = new $this->class;
        $entity->uuid = new Uuid('31111111-1111-1111-1111-111111111111');
        $entity2 = new $this->class;
        $entity2->uuid = new Uuid('41111111-1111-1111-1111-111111111111');

        $this
            ->r
            ->add($entity)
            ->add($entity2);
        $this->uow->commit();
        $all = $this->r->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame('object', (string) $all->type());
        $this->assertSame(2, $all->size());
        $this->assertTrue($all->contains($entity));
        $this->assertTrue($all->contains($entity2));
        $this
            ->r
            ->remove($entity)
            ->remove($entity2);
        $this->uow->commit();
    }

    public function testMatching()
    {
        $entity = new $this->class;
        $entity->uuid = new Uuid('51111111-1111-1111-1111-111111111111');
        $entity->content = 'foo';
        $entity2 = new $this->class;
        $entity2->uuid = new Uuid('61111111-1111-1111-1111-111111111111');
        $entity2->content = 'foobar';
        $entity3 = new $this->class;
        $entity3->uuid = new Uuid('71111111-1111-1111-1111-111111111111');
        $entity3->content = 'bar';

        $this
            ->r
            ->add($entity)
            ->add($entity2)
            ->add($entity3);
        $this->uow->commit();

        $entities = $this->r->matching(new Property('content', '=~', 'foo.*'));

        $this->assertInstanceOf(SetInterface::class, $entities);
        $this->assertSame('object', (string) $entities->type());
        $this->assertSame(2, $entities->size());
        $this->assertTrue($entities->contains($entity));
        $this->assertTrue($entities->contains($entity2));

        $this
            ->r
            ->remove($entity)
            ->remove($entity2)
            ->remove($entity3);
        $this->uow->commit();
    }
}
