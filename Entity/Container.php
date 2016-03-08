<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Exception\IdentityNotManagedException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class Container
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
                new Map(IdentityInterface::class, 'object')
            )
            ->put(
                self::STATE_NEW,
                new Map(IdentityInterface::class, 'object')
            )
            ->put(
                self::STATE_TO_BE_REMOVED,
                new Map(IdentityInterface::class, 'object')
            )
            ->put(
                self::STATE_REMOVED,
                new Map(IdentityInterface::class, 'object')
            );
    }

    /**
     * Inject the given entity with the wished state
     *
     * @param IdentityInterface $identity
     * @param object $entity
     * @param int $wished
     *
     * @return self
     */
    public function push(IdentityInterface $identity, $entity, int $wished): self
    {
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
     * @return MapInterface<IdentityInterface, object>
     */
    public function state(int $state): MapInterface
    {
        return $this->states->get($state);
    }

    /**
     * Remove the entity with the given identity from any state
     *
     * @param IdentityInterface $identity
     *
     * @return self
     */
    public function detach(IdentityInterface $identity): self
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
     * @param IdentityInterface $identity
     *
     * @throws IdentityNotManagedException
     *
     * @return int
     */
    public function stateFor(IdentityInterface $identity): int
    {
        foreach ($this->states as $state => $entities) {
            if ($entities->contains($identity)) {
                return $state;
            }
        }

        throw new IdentityNotManagedException;
    }

    /**
     * Return the entity with the given identity
     *
     * @param IdentityInterface $identity
     *
     * @throws IdentityNotManagedException
     *
     * @return object
     */
    public function get(IdentityInterface $identity)
    {
        return $this
            ->states
            ->get($this->stateFor($identity))
            ->get($identity);
    }
}
