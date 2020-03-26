<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\Manager;
use Innmind\Neo4j\DBAL\Connection;
use Innmind\CommandBus\CommandBus;

final class Transaction implements CommandBus
{
    private CommandBus $handle;
    private Connection $connection;

    public function __construct(CommandBus $handle, Manager $manager)
    {
        $this->handle = $handle;
        $this->connection = $manager->connection();
    }

    public function __invoke(object $command): void
    {
        try {
            $this->connection->openTransaction();
            ($this->handle)($command);
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollback();

            throw $e;
        }
    }
}
