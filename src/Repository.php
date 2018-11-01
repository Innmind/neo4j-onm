<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\SetInterface;

interface Repository
{
    /**
     * Add a new entity to the repository
     */
    public function add(object $entity): self;

    /**
     * Check if the repository has an entity with the given id
     */
    public function has(Identity $identity): bool;

    /**
     * Return the entity with the given id
     *
     * @throws EntityNotFoundException
     */
    public function get(Identity $identity): object;

    /**
     * Try to find the entity with the given id
     */
    public function find(Identity $identity): ?object;

    /**
     * Remove the given entity from the repository
     */
    public function remove(object $entity): self;

    /**
     * Return all the entities from the repository
     *
     * @return SetInterface<object>
     */
    public function all(): SetInterface;

    /**
     * Return all the entities matching the given specification
     *
     * @return SetInterface<object>
     */
    public function matching(SpecificationInterface $specification): SetInterface;
}
