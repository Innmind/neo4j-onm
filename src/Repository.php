<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Exception\EntityNotFound;
use Innmind\Specification\Specification;
use Innmind\Immutable\Set;

interface Repository
{
    /**
     * Add a new entity to the repository
     */
    public function add(object $entity): void;

    /**
     * Check if the repository has an entity with the given id
     */
    public function contains(Identity $identity): bool;

    /**
     * Return the entity with the given id
     *
     * @throws EntityNotFound
     */
    public function get(Identity $identity): object;

    /**
     * Try to find the entity with the given id
     */
    public function find(Identity $identity): ?object;

    /**
     * Remove the given entity from the repository
     */
    public function remove(object $entity): void;

    /**
     * Return all the entities from the repository
     *
     * @return Set<object>
     */
    public function all(): Set;

    /**
     * Return all the entities matching the given specification
     *
     * @return Set<object>
     */
    public function matching(Specification $specification): Set;
}
