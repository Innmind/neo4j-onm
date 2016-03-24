<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

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
    Metadata\Repository
};
use Innmind\Neo4j\DBAL\ConnectionInterface;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $commited = false;
        $conn = $this->getMock(ConnectionInterface::class);
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uow
            ->method('connection')
            ->willReturn($conn);
        $uow
            ->method('commit')
            ->will($this->returnCallback(function() use (&$commited, $uow) {
                $commited = true;

                return $uow;
            }));
        $metadatas = new Metadatas;
        $factory = new RepositoryFactory(
            $uow,
            new MatchTranslator,
            new SpecificationTranslator
        );
        $mock = $this->getMock(RepositoryInterface::class);
        $meta = $this->getMock(EntityInterface::class);
        $meta
            ->method('class')
            ->willReturn(new ClassName('foo'));
        $meta
            ->method('repository')
            ->willReturn(new Repository(get_class($mock)));
        $metadatas->register($meta);

        $m = new Manager(
            $uow,
            $metadatas,
            $factory
        );

        $this->assertInstanceOf(ManagerInterface::class, $m);
        $this->assertSame($conn, $m->connection());
        $this->assertInstanceOf(get_class($mock), $m->repository('foo'));
        $this->assertFalse($commited);
        $this->assertSame($m, $m->flush());
        $this->assertTrue($commited);
    }
}
