<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\MapInterface;

final class EntityUpdated
{
    private $identity;
    private $entity;
    private $changeset;

    /**
     * @param object $entity
     */
    public function __construct(
        IdentityInterface $identity,
        $entity,
        MapInterface $changeset
    ) {
        if (!is_object($entity)) {
            throw new InvalidArgumentException;
        }

        $this->identity = $identity;
        $this->entity = $entity;
        $this->changeset = $changeset;
    }

    public function identity(): IdentityInterface
    {
        return $this->identity;
    }

    /**
     * @return object
     */
    public function entity()
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
