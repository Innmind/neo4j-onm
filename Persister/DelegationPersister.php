<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Immutable\{
    Set,
    SetInterface
};

class DelegationPersister implements PersisterInterface
{
    private $persisters;

    public function __construct(SetInterface $persisters = null)
    {
        $this->persisters = $persisters ?? (new Set(PersisterInterface::class))
            ->add(new InsertPersister)
            ->add(new UpdatePersister)
            ->add(new RemovePersister);

        if ((string) $this->persisters->type() !== PersisterInterface::class) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ConnectionInterface $connection, Container $container)
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
