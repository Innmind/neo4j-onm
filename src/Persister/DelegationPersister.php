<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister,
    Entity\Container,
};
use Innmind\Neo4j\DBAL\Connection;

final class DelegationPersister implements Persister
{
    private array $persisters;

    public function __construct(Persister ...$persisters)
    {
        $this->persisters = $persisters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Connection $connection, Container $container): void
    {
        foreach ($this->persisters as $persist) {
            $persist($connection, $container);
        }
    }
}
