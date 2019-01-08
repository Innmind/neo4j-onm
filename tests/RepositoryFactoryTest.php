<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    RepositoryFactory,
    Repository as RepositoryInterface,
    Metadata\Entity,
    Metadata\Repository,
    Metadatas,
    UnitOfWork,
    Translation\Match\DelegationTranslator as MatchTranslator,
    Translation\Specification\DelegationTranslator as SpecificationTranslator,
    Translation\ResultTranslator,
    Translation\IdentityMatch\DelegationTranslator as IdentityMatchTranslator,
    Entity\Container,
    Identity\Generators,
    EntityFactory\Resolver,
    Persister,
    EntityFactory\EntityFactory,
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RepositoryFactoryTest extends TestCase
{
    private $make;

    public function setUp()
    {
        $this->make = new RepositoryFactory(
            new UnitOfWork(
                $this->createMock(Connection::class),
                $container = new Container,
                new EntityFactory(
                    new ResultTranslator,
                    $generators = new Generators,
                    new Resolver,
                    $container
                ),
                new IdentityMatchTranslator,
                $metadatas = new Metadatas,
                $persister = $this->createMock(Persister::class),
                $generators
            ),
            new MatchTranslator,
            new SpecificationTranslator
        );
    }

    public function testMake()
    {
        $mock = $this->createMock(RepositoryInterface::class);
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('repository')
            ->willReturn(new Repository(get_class($mock)));
        $repo = ($this->make)($meta);

        $this->assertInstanceOf(get_class($mock), $repo);
        $this->assertSame($repo, ($this->make)($meta));
    }

    public function testRegisterRepositoryAtConstruct()
    {
        $meta = $this->createMock(Entity::class);
        $repo = $this->createMock(RepositoryInterface::class);

        $make = new RepositoryFactory(
            new UnitOfWork(
                $this->createMock(Connection::class),
                $container = new Container,
                new EntityFactory(
                    new ResultTranslator,
                    $generators = new Generators,
                    new Resolver,
                    $container
                ),
                new IdentityMatchTranslator,
                $metadatas = new Metadatas,
                $persister = $this->createMock(Persister::class),
                $generators
            ),
            new MatchTranslator,
            new SpecificationTranslator,
            Map::of(Entity::class, RepositoryInterface::class)
                ($meta, $repo)
        );

        $this->assertSame($repo, $make($meta));
    }

    public function testThrowWhenInvalidRepositoriesMapKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type MapInterface<Innmind\Neo4j\ONM\Metadata\Entity, Innmind\Neo4j\ONM\Repository>');

        new RepositoryFactory(
            new UnitOfWork(
                $this->createMock(Connection::class),
                $container = new Container,
                new EntityFactory(
                    new ResultTranslator,
                    $generators = new Generators,
                    new Resolver,
                    $container
                ),
                new IdentityMatchTranslator,
                $metadatas = new Metadatas,
                $persister = $this->createMock(Persister::class),
                $generators
            ),
            new MatchTranslator,
            new SpecificationTranslator,
            new Map('foo', RepositoryInterface::class)
        );
    }

    public function testThrowWhenInvalidRepositoriesMapValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type MapInterface<Innmind\Neo4j\ONM\Metadata\Entity, Innmind\Neo4j\ONM\Repository>');

        new RepositoryFactory(
            new UnitOfWork(
                $this->createMock(Connection::class),
                $container = new Container,
                new EntityFactory(
                    new ResultTranslator,
                    $generators = new Generators,
                    new Resolver,
                    $container
                ),
                new IdentityMatchTranslator,
                $metadatas = new Metadatas,
                $persister = $this->createMock(Persister::class),
                $generators
            ),
            new MatchTranslator,
            new SpecificationTranslator,
            new Map(Entity::class, 'foo')
        );
    }
}
