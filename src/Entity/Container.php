<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Identity,
    Exception\IdentityNotManaged,
    Exception\DomainException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Container
{
    const STATE_MANAGED = 1;
    const STATE_NEW = 2;
    const STATE_TO_BE_REMOVED = 3;
    const STATE_REMOVED = 4;

    private $states;

    public function __construct()
    {
        $this->states = (new Map('int', Map::class))
            ->put(
                self::STATE_MANAGED,
                new Map(Identity::class, 'object')
            )
            ->put(
                self::STATE_NEW,
                new Map(Identity::class, 'object')
            )
            ->put(
                self::STATE_TO_BE_REMOVED,
                new Map(Identity::class, 'object')
            )
            ->put(
                self::STATE_REMOVED,
                new Map(Identity::class, 'object')
            );
    }

    /**
     * Inject the given entity with the wished state
     *
     * @param object $entity
     */
    public function push(Identity $identity, $entity, int $wished): self
    {
        if (!$this->states->contains($wished)) {
            throw new DomainException;
        }

        $this->states = $this->states->map(function(
            int $state,
            MapInterface $entities
        ) use (
            $identity,
            $entity,
            $wished
        ) {
            if ($wished === $state) {
                return $entities->put($identity, $entity);
            }

            return $entities->remove($identity);
        });

        return $this;
    }

    /**
     * Return all the entities of a specific state
     *
     * @param int $state
     *
     * @return MapInterface<Identity, object>
     */
    public function state(int $state): MapInterface
    {
        return $this->states->get($state);
    }

    /**
     * Remove the entity with the given identity from any state
     *
     * @param Identity $identity
     *
     * @return self
     */
    public function detach(Identity $identity): self
    {
        $this->states = $this->states->map(function(
            int $state,
            MapInterface $entities
        ) use (
            $identity
        ) {
            return $entities->remove($identity);
        });

        return $this;
    }

    /**
     * Return the state for the given identity
     *
     * @param Identity $identity
     *
     * @throws IdentityNotManaged
     *
     * @return int
     */
    public function stateFor(Identity $identity): int
    {
        foreach ($this->states as $state => $entities) {
            if ($entities->contains($identity)) {
                return $state;
            }
        }

        throw new IdentityNotManaged;
    }

    /**
     * Return the entity with the given identity
     *
     * @param Identity $identity
     *
     * @throws IdentityNotManaged
     *
     * @return object
     */
    public function get(Identity $identity)
    {
        return $this
            ->states
            ->get($this->stateFor($identity))
            ->get($identity);
    }

    /**
     * Check if the given identity if known by the container
     *
     * @param Identity $identity
     *
     * @return bool
     */
    public function contains(Identity $identity): bool
    {
        try {
            $this->stateFor($identity);

            return true;
        } catch (IdentityNotManaged $e) {
            return false;
        }
    }
}
