<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Persister;

use Innmind\Neo4j\ONM\{
    Persister\DelegationPersister,
    PersisterInterface,
    Entity\Container
};
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Immutable\Set;

class DelegationPersisterTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $p = new DelegationPersister(
            new Set(PersisterInterface::class)
        );
        $this->assertInstanceOf(PersisterInterface::class, $p);
    }

    public function testPersist()
    {
        $p = new DelegationPersister(
            (new Set(PersisterInterface::class))
                ->add(
                    $m1 = $this->getMock(PersisterInterface::class)
                )
                ->add(
                    $m2 = $this->getMock(PersisterInterface::class)
                )
        );
        $count = 0;
        $expectedConn = $this->getMock(ConnectionInterface::class);
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