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
        $p = new DelegationPersister(
            new Stream(PersisterInterface::class)
        );
        $this->assertInstanceOf(PersisterInterface::class, $p);
    }

    public function testPersist()
    {
        $p = new DelegationPersister(
            (new Stream(PersisterInterface::class))
                ->add(
                    $m1 = $this->createMock(PersisterInterface::class)
                )
                ->add(
                    $m2 = $this->createMock(PersisterInterface::class)
                )
        );
        $count = 0;
        $expectedConn = $this->createMock(ConnectionInterface::class);
        $expectedContainer = new Container;
        $m1
            ->method('persist')
            ->will($this->returnCallback(function(
                ConnectionInterface $conn,
                Container $container
            ) use (
                &$count,
                $expectedConn,
                $expectedContainer
            ) {
                $this->assertSame($expectedConn, $conn);
                $this->assertSame($expectedContainer, $container);
                ++$count;
            }));
        $m2
            ->method('persist')
            ->will($this->returnCallback(function(
                ConnectionInterface $conn,
                Container $container
            ) use (
                &$count,
                $expectedConn,
                $expectedContainer
            ) {
                $this->assertSame($expectedConn, $conn);
                $this->assertSame($expectedContainer, $container);
                ++$count;
            }));

        $this->assertSame(null, $p->persist($expectedConn, $expectedContainer));
        $this->assertSame(2, $count);
    }
}
