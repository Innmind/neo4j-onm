<?php

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Event;

class FlushEvent extends Event
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Return the entity manager about to be flushed
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
