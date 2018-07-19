<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\Flush,
    Manager,
};
use Innmind\CommandBus\CommandBusInterface;
use PHPUnit\Framework\TestCase;

class FlushTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBusInterface::class,
            new Flush(
                $this->createMock(CommandBusInterface::class),
                $this->createMock(Manager::class)
            )
        );
    }

    public function testHandle()
    {
        $command = new \stdClass;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects($this->once())
            ->method('handle')
            ->with($command);
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects($this->once())
            ->method('flush');
        $bus = new Flush($commandBus, $manager);

        $this->assertNull($bus->handle($command));
    }
}
