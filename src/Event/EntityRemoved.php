<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\Identity;

final class EntityRemoved
{
    private Identity $identity;
    private object $entity;

    public function __construct(Identity $identity, object $entity)
    {
        $this->identity = $identity;
        $this->entity = $entity;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function entity(): object
    {
        return $this->entity;
    }
}
