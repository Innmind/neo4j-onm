<?php

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\Query;
use Symfony\Component\EventDispatcher\Event;

class PostQueryEvent extends Event
{
    protected $query;
    protected $entities;

    public function __construct(Query $query, \SplObjectStorage $entities)
    {
        $this->query = $query;
        $this->entities = $entities;
    }

    /**
     * Return the query to be executed
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Return the entities returned by the query
     *
     * @return \SplObjectStorage
     */
    public function getEntities()
    {
        return $this->entities;
    }
}
