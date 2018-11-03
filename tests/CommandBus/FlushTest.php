<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\Flush,
    Manager,
};
use Innmind\CommandBus\CommandBus;
use PHPUnit\Framework\TestCase;

class FlushTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBus::class,
            new Flush(
                $this->createMock(CommandBus::class),
                $this->createMock(Manager::class)
            )
        );
    }

    public function testHandle()
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
            ->method('flush');
        $handle = new Flush($commandBus, $manager);

        $this->assertNull($handle($command));
    }
}
