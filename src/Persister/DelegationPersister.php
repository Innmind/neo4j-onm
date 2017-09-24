<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister,
    Entity\Container,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\Immutable\StreamInterface;

final class DelegationPersister implements Persister
{
    private $persisters;

    public function __construct(StreamInterface $persisters)
    {
        if ((string) $persisters->type() !== Persister::class) {
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
                Persister $persister
            ) use (
                $connection,
                $container
            ) {
                $persister->persist($connection, $container);
            });
    }
}
