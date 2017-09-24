<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister\DelegationPersister,
    Persister,
    Entity\Container
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\Immutable\Stream;
use PHPUnit\Framework\TestCase;

class DelegationPersisterTest extends TestCase
{
    public function testInterface()
    {
        $perister = new DelegationPersister(
            new Stream(Persister::class)
        );
        $this->assertInstanceOf(Persister::class, $perister);
    }

    public function testPersist()
    {
        $persister = new DelegationPersister(
            (new Stream(Persister::class))
                ->add(
                    $mock1 = $this->createMock(Persister::class)
                )
                ->add(
                    $mock2 = $this->createMock(Persister::class)
                )
        );
        $connection = $this->createMock(Connection::class);
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
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type StreamInterface<Innmind\Neo4j\ONM\Persister>
     */
    public function testThrowWhenInvalidStream()
    {
        new DelegationPersister(new Stream('callable'));
    }
}
