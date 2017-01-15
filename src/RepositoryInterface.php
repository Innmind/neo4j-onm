<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\SetInterface;

interface RepositoryInterface
{
    /**
     * Add a new entity to the repository
     *
     * @param object $entity
     *
     * @return self
     */
    public function add($entity): self;

    /**
     * Check if the repository has an entity with the given id
     *
     * @param IdentityInterface $identity
     *
     * @return bool
     */
    public function has(IdentityInterface $identity): bool;

    /**
     * Return the entity with the given id
     *
     * @param IdentityInterface $identity
     *
     * @throws EntityNotFoundException
     *
     * @return object
     */
    public function get(IdentityInterface $identity);

    /**
     * Try to find the entity with the given id
     *
     * @param IdentityInterface $identity
     *
     * @return object|null
     */
    public function find(IdentityInterface $identity);

    /**
     * Remove the given entity from the repository
     *
     * @param object $entity
     *
     * @return self
     */
    public function remove($entity): self;

    /**
     * Return all the entities from the repository
     *
     * @return SetInterface<object>
     */
    public function all(): SetInterface;

    /**
     * Return all the entities matching the given specification
     *
     * @param SpecificationInterface $specification
     *
     * @return SetInterface<object>
     */
    public function matching(SpecificationInterface $specification): SetInterface;
}
