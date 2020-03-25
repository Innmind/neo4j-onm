<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\Identity;
use Innmind\Immutable\Map;

final class EntityUpdated
{
    private Identity $identity;
    private object $entity;
    /** @var Map<string, mixed> */
    private Map $changeset;

    /**
     * @param Map<string, mixed> $changeset
     */
    public function __construct(
        Identity $identity,
        object $entity,
        Map $changeset
    ) {
        $this->identity = $identity;
        $this->entity = $entity;
        $this->changeset = $changeset;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function entity(): object
    {
        return $this->entity;
    }

    /**
     * @return Map<string, mixed>
     */
    public function changeset(): Map
    {
        return $this->changeset;
    }
}
