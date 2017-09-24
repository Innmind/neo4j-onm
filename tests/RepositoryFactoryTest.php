<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    RepositoryFactory,
    RepositoryInterface,
    Metadata\EntityInterface,
    Metadata\Repository,
    Metadatas,
    UnitOfWork,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Translation\ResultTranslator,
    Translation\IdentityMatchTranslator,
    Entity\Container,
    Identity\Generators,
    EntityFactory\Resolver,
    PersisterInterface,
    EntityFactory
};
use Innmind\Neo4j\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class RepositoryFactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new RepositoryFactory(
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
                $persister = $this->createMock(PersisterInterface::class),
                $generators
            ),
            new MatchTranslator,
            new SpecificationTranslator
        );
    }

    public function testMake()
    {
        $mock = $this->createMock(RepositoryInterface::class);
        $meta = $this->createMock(EntityInterface::class);
        $meta
            ->method('repository')
            ->willReturn(new Repository(get_class($mock)));
        $repo = $this->factory->make($meta);

        $this->assertInstanceOf(get_class($mock), $repo);
        $this->assertSame($repo, $this->factory->make($meta));
    }

    public function testRegister()
    {
        $meta = $this->createMock(EntityInterface::class);
        $repo = $this->createMock(RepositoryInterface::class);

        $this->assertSame($this->factory, $this->factory->register($meta, $repo));
        $this->assertSame($repo, $this->factory->make($meta));
    }
}
