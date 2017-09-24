<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Manager,
    ManagerInterface,
    UnitOfWork,
    Metadatas,
    RepositoryFactory,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    RepositoryInterface,
    Metadata\EntityInterface,
    Metadata\ClassName,
    Metadata\Alias,
    Metadata\Repository,
    Identity\Uuid,
    Identity\Generators,
    Entity\Container,
    EntityFactory,
    EntityFactory\Resolver,
    Translation\ResultTranslator,
    Translation\IdentityMatchTranslator,
    PersisterInterface
};
use Innmind\Neo4j\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testInterface()
    {
        $mock = $this->createMock(RepositoryInterface::class);
        $conn = $this->createMock(Connection::class);
        $meta = $this->createMock(EntityInterface::class);
        $meta
            ->method('class')
            ->willReturn(new ClassName('foo'));
        $meta
            ->method('alias')
            ->willReturn(new Alias('foo'));
        $meta
            ->method('repository')
            ->willReturn(new Repository(get_class($mock)));
        $uow = new UnitOfWork(
            $conn,
            $container = new Container,
            new EntityFactory(
                new ResultTranslator,
                $generators = new Generators,
                new Resolver,
                $container
            ),
            new IdentityMatchTranslator,
            $metadatas = new Metadatas($meta),
            $persister = $this->createMock(PersisterInterface::class),
            $generators
        );
        $persister
            ->expects($this->once())
            ->method('persist')
            ->with($conn, $container);
        $factory = new RepositoryFactory(
            $uow,
            new MatchTranslator,
            new SpecificationTranslator
        );

        $manager = new Manager(
            $uow,
            $metadatas,
            $factory,
            $generators
        );

        $this->assertInstanceOf(ManagerInterface::class, $manager);
        $this->assertSame($conn, $manager->connection());
        $this->assertInstanceOf(get_class($mock), $manager->repository('foo'));
        $this->assertSame($manager, $manager->flush());
        $this->assertInstanceOf(Uuid::class, $manager->new(Uuid::class));
    }
}
