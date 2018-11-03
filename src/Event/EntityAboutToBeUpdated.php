<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\Identity;
use Innmind\Immutable\MapInterface;

final class EntityAboutToBeUpdated
{
    private $identity;
    private $entity;
    private $changeset;

    public function __construct(
        Identity $identity,
        object $entity,
        MapInterface $changeset
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
     * @return MapInterface<string, mixed>
     */
    public function changeset(): MapInterface
    {
        return $this->changeset;
    }
}
