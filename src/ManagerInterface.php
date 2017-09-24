<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\Connection;

interface ManagerInterface
{
    /**
     * Return the connection used by this manager
     */
    public function connection(): Connection;

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

    /**
     * Return a new identity of the wished type
     *
     * @param string $class
     *
     * @return IdentityInterface
     */
    public function new(string $class): IdentityInterface;
}
