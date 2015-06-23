<?php

namespace Innmind\Neo4j\ONM\Event;

use Symfony\Component\EventDispatcher\Event;

class LifeCycleEvent extends Event
{
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Return the entity about to be manipulated
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
