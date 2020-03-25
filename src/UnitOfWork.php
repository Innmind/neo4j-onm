<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    EntityFactory\EntityFactory,
    Translation\IdentityMatchTranslator,
    Identity\Generators,
    Exception\EntityNotFound,
    Exception\IdentityNotManaged,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\first;
use Innmind\Reflection\ReflectionObject;

final class UnitOfWork
{
    private Connection $connection;
    private Container $container;
    private EntityFactory $makeEntity;
    private IdentityMatchTranslator $match;
    private Metadatas $metadata;
    private Persister $persist;
    private Generators $generators;

    public function __construct(
        Connection $connection,
        Container $container,
        EntityFactory $makeEntity,
        IdentityMatchTranslator $match,
        Metadatas $metadata,
        Persister $persister,
        Generators $generators
    ) {
        $this->connection = $connection;
        $this->container = $container;
        $this->makeEntity = $makeEntity;
        $this->match = $match;
        $this->metadata = $metadata;
        $this->persist = $persister;
        $this->generators = $generators;
    }

    /**
     * Return the connection used by this unit of work
     */
    public function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * Add the given entity to the ones to be persisted
     */
    public function persist(object $entity): self
    {
        $identity = $this->extractIdentity($entity);

        if (!$this->container->contains($identity)) {
            $meta = ($this->metadata)(\get_class($entity));
            $this->container->push(
                $identity,
                $entity,
                State::new(),
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
     */
    public function contains(Identity $identity): bool
    {
        return $this->container->contains($identity);
    }

    /**
     * Return the state for the given identity
     */
    public function stateFor(Identity $identity): State
    {
        return $this->container->stateFor($identity);
    }

    /**
     * Return the entity with the given identifier
     *
     * @throws EntityNotFound
     */
    public function get(string $class, Identity $identity): object
    {
        $meta = ($this->metadata)($class);
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

        $match = ($this->match)($meta, $identity);
        $entities = $this->execute(
            $match->query(),
            $match->variables(),
        );

        if ($entities->size() !== 1) {
            throw new EntityNotFound;
        }

        return first($entities);
    }

    /**
     * Plan the given entity to be removed
     */
    public function remove(object $entity): self
    {
        $identity = $this->extractIdentity($entity);

        try {
            $state = $this->container->stateFor($identity);

            switch ($state) {
                case State::new():
                    $this->container->push(
                        $identity,
                        $entity,
                        State::removed(),
                    );
                    break;

                case State::managed():
                    $this->container->push(
                        $identity,
                        $entity,
                        State::toBeRemoved(),
                    );
                    break;
            }
        } catch (IdentityNotManaged $e) {
            //pass
        }

        return $this;
    }

    /**
     * Detach the given entity from the unit of work
     */
    public function detach(object $entity): self
    {
        $this->container->detach(
            $this->extractIdentity($entity),
        );

        return $this;
    }

    /**
     * Execute the given query
     *
     * @param Map<string, Metadata\Entity> $variables
     *
     * @return Set<object>
     */
    public function execute(Query $query, Map $variables): Set
    {
        return ($this->makeEntity)(
            $this->connection->execute($query),
            $variables,
        );
    }

    /**
     * Send the modifications to the database
     */
    public function commit(): self
    {
        ($this->persist)($this->connection, $this->container);

        return $this;
    }

    /**
     * Extract the identity object from the given entity
     */
    private function extractIdentity(object $entity): Identity
    {
        $identity = ($this->metadata)(\get_class($entity))->identity()->property();

        /** @var Identity */
        return ReflectionObject::of($entity)
            ->extract($identity)
            ->get($identity);
    }
}
