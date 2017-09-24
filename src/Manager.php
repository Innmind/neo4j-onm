<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\Connection;

interface Manager
{
    /**
     * Return the connection used by this manager
     */
    public function connection(): Connection;

    /**
     * Return an entity repository
     *
     * @param string $class
     */
    public function repository(string $class): Repository;

    /**
     * Persist all the entities' modifications
     */
    public function flush(): self;

    /**
     * Return a new identity of the wished type
     *
     * @param string $class
     */
    public function new(string $class): Identity;
}
