<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\Manager;
use Innmind\CommandBus\CommandBusInterface;

final class Transaction implements CommandBusInterface
{
    private $commandBus;
    private $connection;

    public function __construct(
        CommandBusInterface $commandBus,
        Manager $manager
    ) {
        $this->commandBus = $commandBus;
        $this->connection = $manager->connection();
    }

    public function handle($command)
    {
        try {
            $this->connection->openTransaction();
            $this->commandBus->handle($command);
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollback();

            throw $e;
        }
    }
}
