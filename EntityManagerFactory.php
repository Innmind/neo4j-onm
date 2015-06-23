<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EntityManagerFactory
{
    /**
     * Create a new entity manager instance
     *
     * @param array $conn Connection parameters
     * @param Configuration $config
     *
     * @return EntityManagerInterface
     */
    public static function make(array $conn, Configuration $config, EventDispatcherInterface $dispatcher = null)
    {
        if ($dispatcher === null) {
            $dispatcher = new EventDispatcher;
        }

        $repoFactory = new RepositoryFactory(
            $config->getIdentityMap(),
            $config->getMetadataRegistry()
        );

        $config->setRepositoryFactory($repoFactory);

        $connection = ConnectionFactory::make($conn, $dispatcher);
        return new EntityManager($connection, $config, $dispatcher);
    }
}
