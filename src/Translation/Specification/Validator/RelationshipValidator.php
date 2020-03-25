<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator,
    Metadata\Entity,
    Metadata\Relationship,
    Exception\InvalidArgumentException,
};
use Innmind\Specification\{
    Comparator,
    Composite,
    Not,
    Specification,
};

final class RelationshipValidator implements Validator
{
    public function __invoke(Specification $specification, Entity $meta): bool
    {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        switch (true) {
            case $specification instanceof Comparator:
                return $this->isValidProperty(
                    $specification->property(),
                    $meta
                );

            case $specification instanceof Composite:
                if (!($this)($specification->left(), $meta)) {
                    return false;
                }

                return ($this)($specification->right(), $meta);

            case $specification instanceof Not:
                return ($this)($specification->specification(), $meta);
        }

        return false;
    }

    private function isValidProperty(string $property, Relationship $meta): bool
    {
        if ($meta->properties()->contains($property)) {
            return true;
        }

        return $meta->startNode()->property() === $property ||
            $meta->endNode()->property() === $property;
    }
}
