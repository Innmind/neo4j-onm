<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Exception;

use Innmind\Neo4j\ONM\Metadata\{
    Child,
    Entity,
};

final class MoreThanOneRelationshipFound extends RuntimeException
{
    private $child;
    private $entity;

    public static function for(Child $child): self
    {
        $exception = new self;
        $exception->child = $child;

        return $exception;
    }

    public function on(Entity $entity): self
    {
        $exception = new self(sprintf(
            'More than one relationship found on "%s::%s"',
            $entity->class(),
            $this->child->relationship()->property()
        ));
        $exception->child = $this->child;
        $exception->entity = $entity;

        return $exception;
    }

    public function child(): Child
    {
        return $this->child;
    }

    public function entity(): Entity
    {
        return $this->entity;
    }
}
