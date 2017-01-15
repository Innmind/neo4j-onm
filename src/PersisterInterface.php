<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Entity\Container;
use Innmind\Neo4j\DBAL\ConnectionInterface;

interface PersisterInterface
{
    /**
     * Use the given collection to persist modifications in the given container
     *
     * @param ConnectionInterface $connection
     * @param Container $container
     *
     * @return void
     */
    public function persist(ConnectionInterface $connection, Container $container);
}
