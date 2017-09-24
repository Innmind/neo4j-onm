<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\Immutable\StreamInterface;

final class DelegationPersister implements PersisterInterface
{
    private $persisters;

    public function __construct(StreamInterface $persisters)
    {
        if ((string) $persisters->type() !== PersisterInterface::class) {
            throw new InvalidArgumentException;
        }

        $this->persisters = $persisters;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Connection $connection, Container $container)
    {
        $this
            ->persisters
            ->foreach(function(
                PersisterInterface $persister
            ) use (
                $connection,
                $container
            ) {
                $persister->persist($connection, $container);
            });
    }
}
