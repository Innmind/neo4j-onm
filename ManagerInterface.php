<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionInterface;

interface ManagerInterface
{
    /**
     * Return the connection used by this manager
     *
     * @return ConnectionInterface
     */
    public function connection(): ConnectionInterface;

    /**
     * Return an entity repository
     *
     * @param string $class
     *
     * @return EntityRepositoryInterface
     */
    public function repository(string $class): RepositoryInterface;

    /**
     * Persist all the entities' modifications
     *
     * @return self
     */
    public function flush(): self;
}
