<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Exception;

use Innmind\Neo4j\ONM\Metadata\{
    Aggregate\Child,
    Entity,
};

final class MoreThanOneRelationshipFound extends RuntimeException
{
    private Child $child;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Entity $entity;

    private function __construct(string $message, Child $child)
    {
        parent::__construct($message);
        $this->child = $child;
    }

    public static function for(Child $child): self
    {
        return new self('', $child);
    }

    public function on(Entity $entity): self
    {
        $exception = new self(
            \sprintf(
                'More than one relationship found on "%s::%s"',
                (string) $entity->class(),
                $this->child->relationship()->property(),
            ),
            $this->child,
        );
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
