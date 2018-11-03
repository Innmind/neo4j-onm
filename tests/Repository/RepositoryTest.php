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
use Innmind\Neo4j\DBAL\ConnectionFactory;
use Innmind\EventBus\EventBus;
use Innmind\HttpTransport\GuzzleTransport;
use Innmind\Http\{
    Translator\Response\Psr7Translator,
    Factory\Header\Factories,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    Map,
};
use GuzzleHttp\Client;
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
            ->useTransport(
                new GuzzleTransport(
                    new Client,
                    new Psr7Translator(Factories::default())
                )
            )
            ->build();
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

        $this->expectException(EntityNotFound::class);
        $this->r->get($entity->uuid);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\EntityNotFound
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
