<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionInterface;

class Manager implements ManagerInterface
{
    private $unitOfWork;
    private $metadatas;
    private $repositoryFactory;

    public function __construct(
        UnitOfWork $unitOfWork,
        Metadatas $metadatas,
        RepositoryFactory $repositoryFactory
    ) {
        $this->unitOfWork = $unitOfWork;
        $this->metadatas = $metadatas;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
    {
        return $this->unitOfWork->connection();
    }

    /**
     * {@inheritdoc}
     */
    public function repository(string $class): RepositoryInterface
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
}
