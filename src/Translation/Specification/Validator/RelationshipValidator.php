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
    ComparatorInterface,
    CompositeInterface,
    NotInterface,
    SpecificationInterface,
};

final class RelationshipValidator implements Validator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(
        SpecificationInterface $specification,
        Entity $meta
    ): bool {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        switch (true) {
            case $specification instanceof ComparatorInterface:
                return $this->isValidProperty(
                    $specification->property(),
                    $meta
                );

            case $specification instanceof CompositeInterface:
                if (!($this)($specification->left(), $meta)) {
                    return false;
                }

                return ($this)($specification->right(), $meta);

            case $specification instanceof NotInterface:
                return ($this)($specification->specification(), $meta);
        }

        return false;
    }

    private function isValidProperty(
        string $property,
        Entity $meta
    ): bool {
        if ($meta->properties()->contains($property)) {
            return true;
        }

        return $meta->startNode()->property() === $property ||
            $meta->endNode()->property() === $property;
    }
}
