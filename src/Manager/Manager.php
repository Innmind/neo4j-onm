<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Manager;

use Innmind\Neo4j\ONM\{
    Manager as ManagerInterface,
    UnitOfWork,
    Metadatas,
    RepositoryFactory,
    Repository,
    Identity,
    Identity\Generators,
};
use Innmind\Neo4j\DBAL\Connection;

final class Manager implements ManagerInterface
{
    private UnitOfWork $unitOfWork;
    private Metadatas $metadata;
    private RepositoryFactory $make;
    private Generators $generators;

    public function __construct(
        UnitOfWork $unitOfWork,
        Metadatas $metadata,
        RepositoryFactory $make,
        Generators $generators
    ) {
        $this->unitOfWork = $unitOfWork;
        $this->metadata = $metadata;
        $this->make = $make;
        $this->generators = $generators;
    }

    public function connection(): Connection
    {
        return $this->unitOfWork->connection();
    }

    public function repository(string $class): Repository
    {
        return ($this->make)(
            ($this->metadata)($class),
        );
    }

    public function flush(): ManagerInterface
    {
        $this->unitOfWork->commit();

        return $this;
    }

    public function identities(): Generators
    {
        return $this->generators;
    }
}
