<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\Manager;
use Innmind\CommandBus\CommandBusInterface;

final class Flush implements CommandBusInterface
{
    private $commandBus;
    private $manager;

    public function __construct(
        CommandBusInterface $commandBus,
        Manager $manager
    ) {
        $this->commandBus = $commandBus;
        $this->manager = $manager;
    }

    public function handle($command)
    {
        $this->commandBus->handle($command);
        $this->manager->flush();
    }
}
