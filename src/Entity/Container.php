<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\Container\State,
    Identity,
    Exception\IdentityNotManaged,
    Exception\DomainException,
};
use Innmind\Immutable\Map;

final class Container
{
    /** @var Map<State, Map<Identity, object>> */
    private Map $states;

    public function __construct()
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<State, Map<Identity, object>>
         */
        $this->states = Map::of(State::class, Map::class)
            (State::managed(), Map::of(Identity::class, 'object'))
            (State::new(), Map::of(Identity::class, 'object'))
            (State::toBeRemoved(), Map::of(Identity::class, 'object'))
            (State::removed(), Map::of(Identity::class, 'object'));
    }

    /**
     * Inject the given entity with the wished state
     */
    public function push(Identity $identity, object $entity, State $wished): void
    {
        if (!$this->states->contains($wished)) {
            throw new DomainException;
        }

        $this->states = $this->states->map(static function(
            State $state,
            Map $entities
        ) use (
            $identity,
            $entity,
            $wished
        ) {
            if ($wished === $state) {
                return ($entities)($identity, $entity);
            }

            return $entities->remove($identity);
        });
    }

    /**
     * Return all the entities of a specific state
     *
     * @return Map<Identity, object>
     */
    public function state(State $state): Map
    {
        return $this->states->get($state);
    }

    /**
     * Remove the entity with the given identity from any state
     */
    public function detach(Identity $identity): void
    {
        $this->states = $this->states->map(static function(
            State $state,
            Map $entities
        ) use (
            $identity
        ) {
            return $entities->remove($identity);
        });
    }

    /**
     * Return the state for the given identity
     *
     * @throws IdentityNotManaged
     */
    public function stateFor(Identity $identity): State
    {
        $state = $this->states->reduce(
            null,
            static function(?State $current, State $state, Map $entities) use ($identity): ?State {
                if ($current) {
                    return $current;
                }

                if ($entities->contains($identity)) {
                    return $state;
                }

                return null;
            },
        );

        if (!$state) {
            throw new IdentityNotManaged;
        }

        return $state;
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
