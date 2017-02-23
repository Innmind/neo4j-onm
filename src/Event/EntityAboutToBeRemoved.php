<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Exception\InvalidArgumentException
};

final class EntityAboutToBeRemoved
{
    private $identity;
    private $entity;

    /**
     * @param object $entity
     */
    public function __construct(IdentityInterface $identity, $entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException;
        }

        $this->identity = $identity;
        $this->entity = $entity;
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
}
