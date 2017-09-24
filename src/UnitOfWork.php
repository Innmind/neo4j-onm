<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    EntityFactory\EntityFactory,
    Translation\IdentityMatchTranslator,
    Identity\Generators,
    Exception\EntityNotFoundException,
    Exception\IdentityNotManagedException
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface
};
use Innmind\Reflection\ReflectionObject;

final class UnitOfWork
{
    private $connection;
    private $container;
    private $entityFactory;
    private $identityMatchTranslator;
    private $metadatas;
    private $persister;
    private $generators;

    public function __construct(
        Connection $connection,
        Container $container,
        EntityFactory $entityFactory,
        IdentityMatchTranslator $identityMatchTranslator,
        Metadatas $metadatas,
        Persister $persister,
        Generators $generators
    ) {
        $this->connection = $connection;
        $this->container = $container;
        $this->entityFactory = $entityFactory;
        $this->identityMatchTranslator = $identityMatchTranslator;
        $this->metadatas = $metadatas;
        $this->persister = $persister;
        $this->generators = $generators;
    }

    /**
     * Return the connection used by this unit of work
     *
     * @return Connection
     */
    public function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * Add the given entity to the ones to be persisted
     *
     * @param object $entity
     *
     * @return self
     */
    public function persist($entity): self
    {
        $identity = $this->extractIdentity($entity);

        if (!$this->container->contains($identity)) {
            $meta = $this->metadatas->get(get_class($entity));
            $this->container->push(
                $identity,
                $entity,
                Container::STATE_NEW
            );
            $this
                ->generators
                ->get($meta->identity()->type())
                ->add($identity);
        }

        return $this;
    }

    /**
     * Check if the given identity already has been loaded
     *
     * @param Identity $identity
     *
     * @return bool
     */
    public function contains(Identity $identity): bool
    {
        return $this->container->contains($identity);
    }

    /**
     * Return the state for the given identity
     *
     * @param Identity $identity
     *
     * @return int
     */
    public function stateFor(Identity $identity): int
    {
        return $this->container->stateFor($identity);
    }

    /**
     * Return the entity with the given identifier
     *
     * @param string $class
     * @param Identity $identity
     *
     * @throws EntityNotFoundException
     *
     * @return object
     */
    public function get(string $class, Identity $identity)
    {
        $meta = $this->metadatas->get($class);
        $generator = $this
            ->generators
            ->get($meta->identity()->type());

        if ($generator->knows($identity->value())) {
            $identity = $generator->for($identity->value());
        } else {
            $generator->add($identity);
        }

        if ($this->container->contains($identity)) {
            return $this->container->get($identity);
        }

        $match = $this->identityMatchTranslator->translate($meta, $identity);
        $entities = $this->execute(
            $match->query(),
            $match->variables()
        );

        if ($entities->size() !== 1) {
            throw new EntityNotFoundException;
        }

        return $entities->current();
    }

    /**
     * Plan the given entity to be removed
     *
     * @param object $entity
     *
     * @return self
     */
    public function remove($entity): self
    {
        $identity = $this->extractIdentity($entity);

        try {
            $state = $this->container->stateFor($identity);

            switch ($state) {
                case Container::STATE_NEW:
                    $this->container->push(
                        $identity,
                        $entity,
                        Container::STATE_REMOVED
                    );
                    break;

                case Container::STATE_MANAGED:
                    $this->container->push(
                        $identity,
                        $entity,
                        Container::STATE_TO_BE_REMOVED
                    );
                    break;
            }
        } catch (IdentityNotManagedException $e) {
            //pass
        }

        return $this;
    }

    /**
     * Detach the given entity from the unit of work
     *
     * @param object $entity
     *
     * @return self
     */
    public function detach($entity): self
    {
        $this->container->detach(
            $this->extractIdentity($entity)
        );

        return $this;
    }

    /**
     * Execute the given query
     *
     * @param MapInterface<string, EntityInterface> $variables
     *
     * @return SetInterface<object>
     */
    public function execute(
        Query $query,
        MapInterface $variables
    ): SetInterface {
        return $this->entityFactory->make(
            $this->connection->execute($query),
            $variables
        );
    }

    /**
     * Send the modifications to the database
     *
     * @return self
     */
    public function commit(): self
    {
        $this->persister->persist($this->connection, $this->container);

        return $this;
    }

    /**
     * Extract the identity object from the given entity
     *
     * @param object $entity
     *
     * @return Identity
     */
    private function extractIdentity($entity): Identity
    {
        $identity = $this
            ->metadatas
            ->get(get_class($entity))
            ->identity()
            ->property();

        return (new ReflectionObject($entity))
            ->extract([$identity])
            ->get($identity);
    }
}
