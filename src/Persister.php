<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Entity\Container;
use Innmind\Neo4j\DBAL\Connection;

interface Persister
{
    /**
     * Use the given collection to persist modifications in the given container
     */
    public function persist(Connection $connection, Container $container): void;
}
