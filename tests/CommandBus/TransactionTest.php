<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\Transaction,
    Manager,
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\CommandBus\CommandBus;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBus::class,
            new Transaction(
                $this->createMock(CommandBus::class),
                $this->createMock(Manager::class)
            )
        );
    }

    public function testCommit()
    {
        $command = new \stdClass;
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus
            ->expects($this->once())
            ->method('__invoke')
            ->with($command);
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects($this->once())
            ->method('connection')
            ->willReturn($connection = $this->createMock(Connection::class));
        $connection
            ->expects($this->once())
            ->method('openTransaction');
        $connection
            ->expects($this->once())
            ->method('commit');
        $handle = new Transaction($commandBus, $manager);

        $this->assertNull($handle($command));
    }

    public function testRollback()
    {
        $this->expectException(\RuntimeException::class);

        $command = new \stdClass;
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus
            ->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->will($this->throwException(new \RuntimeException));
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects($this->once())
            ->method('connection')
            ->willReturn($connection = $this->createMock(Connection::class));
        $connection
            ->expects($this->once())
            ->method('openTransaction');
        $connection
            ->expects($this->once())
            ->method('rollback');
        $handle = new Transaction($commandBus, $manager);

        $this->assertNull($handle($command));
    }
}
