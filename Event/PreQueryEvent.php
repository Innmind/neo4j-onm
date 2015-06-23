<?php

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\Query;
use Symfony\Component\EventDispatcher\Event;

class PreQueryEvent extends Event
{
    protected $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
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
}
