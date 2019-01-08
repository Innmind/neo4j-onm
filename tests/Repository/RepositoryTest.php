<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Repository;

use Innmind\Neo4j\ONM\{
    Repository\Repository,
    Repository as RepositoryInterface,
    UnitOfWork,
    Entity\Container,
    EntityFactory\EntityFactory,
    Translation\ResultTranslator,
    Translation\Match\DelegationTranslator as MatchTranslator,
    Translation\Specification\DelegationTranslator as SpecificationTranslator,
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
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Identity\Uuid,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Type\StringType,
    Type,
    Exception\EntityNotFound,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use function Innmind\Neo4j\DBAL\bootstrap as dbal;
use Innmind\EventBus\EventBus;
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Url\Url;
use Innmind\TimeContinuum\TimeContinuum\Earth;
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    SetInterface,
    Set,
    Map,
};
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private $repository;
    private $class;
    private $uow;

    public function setUp()
    {
        $entity = new class {
            public $uuid;
            public $content;
        };
        $this->class = get_class($entity);

        $conn = dbal(
            http()['default'](),
            new Earth,
            Url::fromString('http://neo4j:ci@localhost:7474/')
        );
        $container = new Container;
        $entityFactory = new EntityFactory(
            new ResultTranslator,
            $generators = new Generators,
            new Resolver(
                new RelationshipFactory($generators)
            ),
            $container
        );
        $metadatas = new Metadatas(
            $meta = Aggregate::of(
                new ClassName($this->class),
                new Identity('uuid', Uuid::class),
                Set::of('string', 'Label'),
                Map::of('string', Type::class)
                    ('content', StringType::nullable())
            )
        );
        $changeset = new ChangesetComputer;
        $extractor = new DataExtractor($metadatas);
        $eventBus = $this->createMock(EventBus::class);

        $uow = new UnitOfWork(
            $conn,
            $container,
            $entityFactory,
            new IdentityMatchTranslator,
            $metadatas,
            new DelegationPersister(
                new InsertPersister(
                    $changeset,
                    $eventBus,
                    $extractor,
                    $metadatas
                ),
                new UpdatePersister(
                    $changeset,
                    $eventBus,
                    $extractor,
                    $metadatas
                ),
                new RemovePersister(
                    $changeset,
                    $eventBus,
                    $metadatas
                )
            ),
            $generators
        );

        $this->repository = new Repository(
            $this->uow = $uow,
            new MatchTranslator,
            new SpecificationTranslator,
            $meta
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(RepositoryInterface::class, $this->repository);

        $entity = new $this->class;
        $entity->uuid = new Uuid('21111111-1111-1111-1111-111111111111');

        $this->assertFalse($this->repository->has($entity->uuid));
        $this->assertSame($this->repository, $this->repository->add($entity));
        $this->assertTrue($this->repository->has($entity->uuid));
        $this->assertSame(
            $entity,
            $this->repository->get($entity->uuid)
        );
        $this->assertSame($this->repository, $this->repository->remove($entity));
        $this->assertFalse($this->repository->has($entity->uuid));

        $this->expectException(EntityNotFound::class);
        $this->repository->get($entity->uuid);
    }

    public function testThrowWhenGettingUnknownEntity()
    {
        $this->expectException(EntityNotFound::class);

        $this->repository->get(new Uuid('24111111-1111-1111-1111-111111111111'));
    }

    public function testDoesntFind()
    {
        $this->assertSame(
            null,
            $this->repository->find(new Uuid('24111111-1111-1111-1111-111111111111'))
        );
    }

    public function testAll()
    {
        $entity = new $this->class;
        $entity->uuid = new Uuid('31111111-1111-1111-1111-111111111111');
        $entity2 = new $this->class;
        $entity2->uuid = new Uuid('41111111-1111-1111-1111-111111111111');

        $this
            ->repository
            ->add($entity)
            ->add($entity2);
        $this->uow->commit();
        $all = $this->repository->all();

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame('object', (string) $all->type());
        $this->assertSame(2, $all->size());
        $this->assertTrue($all->contains($entity));
        $this->assertTrue($all->contains($entity2));
        $this
            ->repository
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
            ->repository
            ->add($entity)
            ->add($entity2)
            ->add($entity3);
        $this->uow->commit();

        $entities = $this->repository->matching(new Property('content', Sign::contains(), 'foo.*'));

        $this->assertInstanceOf(SetInterface::class, $entities);
        $this->assertSame('object', (string) $entities->type());
        $this->assertSame(2, $entities->size());
        $this->assertTrue($entities->contains($entity));
        $this->assertTrue($entities->contains($entity2));

        $this
            ->repository
            ->remove($entity)
            ->remove($entity2)
            ->remove($entity3);
        $this->uow->commit();
    }
}
