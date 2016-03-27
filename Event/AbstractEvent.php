<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\IdentityInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    private $identity;
    private $entity;

    public function __construct(IdentityInterface $identity, $entity)
    {
        $this->identity = $identity;
        $this->entity = $entity;
    }

    public function identity(): IdentityInterface
    {
        return $this->identity;
    }

    public function entity()
    {
        return $this->entity;
    }
}
