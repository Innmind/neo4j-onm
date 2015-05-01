<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface EntityManagerInterface
{
    /**
     * Get the connection used by the entity manager
     *
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * Begin a new transaction
     *
     * @return EntityManagerInterface self
     */
    public function beginTransaction();

    /**
     * Commit the opened transaction
     *
     * @return EntityManagerInterface self
     */
    public function commit();

    /**
     * Rollback the currently opened transaction
     *
     * @return EntityManagerInterface self
     */
    public function rollback();

    /**
     * Get the event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getDispatcher();

    /**
     * Return the UnitOfWork
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork();

    /**
     * Find the node associated to the given class for the given id
     *
     * @param string $class
     * @param int $id
     *
     * @return object
     */
    public function find($class, $id);

    /**
     * Manage the given object, so it can be inserted/updated at flush time
     *
     * @param object $entity
     *
     * @return EntityManagerInterface self
     */
    public function persist($entity);

    /**
     * Plan the given entity to be removed at flush time
     *
     * @param object $entity
     *
     * @return EntityManagerInterface self
     */
    public function remove($entity);

    /**
     * Detach all the objects for the given alias from this entity manager
     *
     * If no alias given, it will detach all entities
     *
     * @param string $alias
     *
     * @return EntityManagerInterface self
     */
    public function clear($alias = null);

    /**
     * Detach the given entity from the entity manager
     *
     * @param object $entity
     *
     * @return EntityManagerInterface self
     */
    public function detach($entity);

    /**
     * Reload all properties for the given entity from the database
     * overriding non saved data
     *
     * @param object $entity
     *
     * @return EntityManagerInterface self
     */
    public function refresh($entity);

    /**
     * Commit all the changes to the database
     *
     * @return EntityManagerInterface self
     */
    public function flush();

    /**
     * Return the repository object for the given alias/class name
     *
     * @param string $alias
     *
     * @return Repository
     */
    public function getRepository($alias);

    /**
     * Check if the given entity is managed by the entity manager
     *
     * @param object $entity
     *
     * @return bool
     */
    public function contains($entity);
}
