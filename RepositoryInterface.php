<?php

namespace Innmind\Neo4j\ONM;

interface RepositoryInterface
{
    /**
     * Detach all entities of this repository
     *
     * @return RepositoryInterface self
     */
    public function clear();

    /**
     * Find a node for the specified id
     *
     * @param mixed $id
     *
     * @return object
     */
    public function find($id);

    /**
     * Find all the nodes
     *
     * @return SplObjectStorage
     */
    public function findAll();

    /**
     * Find one node matching the specified criteria
     *
     * @param array $criteria
     * @param array $orderBy
     *
     * @return object|null
     */
    public function findOneBy(array $criteria, array $orderBy = null);

    /**
     * Find all nodes matching the specified criteria
     *
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $skip
     *
     * @return SplObjectStorage
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $skip = null);
}
