<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\ValidatorInterface,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Exception\InvalidArgumentException
};
use Innmind\Specification\{
    ComparatorInterface,
    CompositeInterface,
    NotInterface,
    SpecificationInterface
};
use Innmind\Immutable\StringPrimitive as Str;

class RelationshipValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(
        SpecificationInterface $specification,
        EntityInterface $meta
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
                if (!$this->validate($specification->left(), $meta)) {
                    return false;
                }

                return $this->validate($specification->right(), $meta);

            case $specification instanceof NotInterface:
                return $this->validate($specification->specification(), $meta);
        }

        return false;
    }

    private function isValidProperty(
        string $property,
        EntityInterface $meta
    ): bool {
        if ($meta->properties()->contains($property)) {
            return true;
        }

        return $meta->startNode()->property() === $property ||
            $meta->endNode()->property() === $property;
    }
}
