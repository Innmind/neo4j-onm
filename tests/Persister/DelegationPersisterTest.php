<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\DelegationPersister,
    PersisterInterface,
    Entity\Container
};
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Immutable\Stream;
use PHPUnit\Framework\TestCase;

class DelegationPersisterTest extends TestCase
{
    public function testInterface()
    {
        $perister = new DelegationPersister(
            new Stream(PersisterInterface::class)
        );
        $this->assertInstanceOf(PersisterInterface::class, $perister);
    }

    public function testPersist()
    {
        $persister = new DelegationPersister(
            (new Stream(PersisterInterface::class))
                ->add(
                    $mock1 = $this->createMock(PersisterInterface::class)
                )
                ->add(
                    $mock2 = $this->createMock(PersisterInterface::class)
                )
        );
        $connection = $this->createMock(ConnectionInterface::class);
        $container = new Container;
        $mock1
            ->expects($this->once())
            ->method('persist')
            ->with($connection, $container);
        $mock2
            ->expects($this->once())
            ->method('persist')
            ->with($connection, $container);

        $this->assertNull($persister->persist($connection, $container));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidStream()
    {
        new DelegationPersister(new Stream('callable'));
    }
}
