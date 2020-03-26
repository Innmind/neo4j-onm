<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\Manager;
use Innmind\CommandBus\CommandBus;

final class Flush implements CommandBus
{
    private CommandBus $handle;
    private Manager $manager;

    public function __construct(CommandBus $handle, Manager $manager)
    {
        $this->handle = $handle;
        $this->manager = $manager;
    }

    public function __invoke(object $command): void
    {
        ($this->handle)($command);
        $this->manager->flush();
    }
}
