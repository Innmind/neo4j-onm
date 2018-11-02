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
    private $unitOfWork;
    private $metadatas;
    private $repositoryFactory;
    private $generators;

    public function __construct(
        UnitOfWork $unitOfWork,
        Metadatas $metadatas,
        RepositoryFactory $repositoryFactory,
        Generators $generators
    ) {
        $this->unitOfWork = $unitOfWork;
        $this->metadatas = $metadatas;
        $this->repositoryFactory = $repositoryFactory;
        $this->generators = $generators;
    }

    public function connection(): Connection
    {
        return $this->unitOfWork->connection();
    }

    public function repository(string $class): Repository
    {
        return $this->repositoryFactory->make(
            $this->metadatas->get($class)
        );
    }

    /**
     * {@inheritdoc}
     */
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
