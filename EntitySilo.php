<?php

namespace Innmind\Neo4j\ONM;

/**
 * Holds all the entities loaded by the manager
 */
class EntitySilo
{
    protected $entities;
    protected $loaded = [];

    public function __construct()
    {
        $this->entities = new \SplObjectStorage;
    }

    /**
     * Check if the given id for the given class is loaded
     *
     * @param string $class
     * @param int|string $id
     *
     * @return bool
     */
    public function has($class, $id)
    {
        if (!isset($this->loaded[(string) $class])) {
            return false;
        }

        return isset($this->loaded[(string) $class][$id]);
    }

    /**
     * Add the entity to repository
     *
     * @param object $entity
     * @param string $class
     * @param int|string $id
     *
     * @return EntityRepository self
     */
    public function add($entity, $class, $id)
    {
        if (!isset($this->loaded[(string) $class])) {
            $this->loaded[(string) $class] = [];
        }

        $this->loaded[(string) $class][$id] = $entity;
        $this->entities->attach($entity, ['class' => (string) $class, 'id' => $id]);

        return $this;
    }

    /**
     * Return the entity for the given class and id
     *
     * @param string $class
     * @param int|string $id
     *
     * @return object
     */
    public function get($class, $id)
    {
        return $this->loaded[(string) $class][$id];
    }

    /**
     * Check if the entity has already been added to the silo
     *
     * @param object $entity
     *
     * @return bool
     */
    public function contains($entity)
    {
        return $this->entities->contains($entity);
    }
}
