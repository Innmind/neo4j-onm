<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Identity\Generators;
use Innmind\Neo4j\DBAL\ConnectionInterface;

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

    /**
     * {@inheritdoc}
     */
    public function new(string $class): IdentityInterface
    {
        return $this
            ->generators
            ->get($class)
            ->new();
    }
}
