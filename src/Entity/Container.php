<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\Container\State,
    Identity,
    Exception\IdentityNotManaged,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Container
{
    private $states;

    public function __construct()
    {
        $this->states = Map::of(State::class, Map::class)
            (State::managed(), Map::of(Identity::class, 'object'))
            (State::new(), Map::of(Identity::class, 'object'))
            (State::toBeRemoved(), Map::of(Identity::class, 'object'))
            (State::removed(), Map::of(Identity::class, 'object'));
    }

    /**
     * Inject the given entity with the wished state
     */
    public function push(Identity $identity, object $entity, State $wished): self
    {
        if (!$this->states->contains($wished)) {
            throw new DomainException;
        }

        $this->states = $this->states->map(static function(
            State $state,
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
     * @return MapInterface<Identity, object>
     */
    public function state(State $state): MapInterface
    {
        return $this->states->get($state);
    }

    /**
     * Remove the entity with the given identity from any state
     */
    public function detach(Identity $identity): self
    {
        $this->states = $this->states->map(static function(
            State $state,
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
     * @throws IdentityNotManaged
     */
    public function stateFor(Identity $identity): State
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
     * @throws IdentityNotManaged
     */
    public function get(Identity $identity): object
    {
        return $this
            ->states
            ->get($this->stateFor($identity))
            ->get($identity);
    }

    /**
     * Check if the given identity if known by the container
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
