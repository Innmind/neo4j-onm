<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\DelegationPersister,
    Persister,
    Entity\Container
};
use Innmind\Neo4j\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DelegationPersisterTest extends TestCase
{
    public function testInterface()
    {
        $perister = new DelegationPersister;
        $this->assertInstanceOf(Persister::class, $perister);
    }

    public function testPersist()
    {
        $persist = new DelegationPersister(
            $mock1 = $this->createMock(Persister::class),
            $mock2 = $this->createMock(Persister::class)
        );
        $connection = $this->createMock(Connection::class);
        $container = new Container;
        $mock1
            ->expects($this->once())
            ->method('__invoke')
            ->with($connection, $container);
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($connection, $container);

        $this->assertNull($persist($connection, $container));
    }
}
