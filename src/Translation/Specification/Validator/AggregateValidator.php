<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\ValidatorInterface,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Exception\InvalidArgumentException
};
use Innmind\Specification\{
    ComparatorInterface,
    CompositeInterface,
    NotInterface,
    SpecificationInterface
};
use Innmind\Immutable\Str;

class AggregateValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(
        SpecificationInterface $specification,
        EntityInterface $meta
    ): bool {
        if (!$meta instanceof Aggregate) {
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

        $property = new Str($property);

        if (!$property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/')) {
            return false;
        }

        $pieces = $property->split('.');
        $piece = (string) $pieces->get(0);

        if (!$meta->children()->contains($piece)) {
            return false;
        }

        $child = $meta->children()->get($piece);
        $relationship = $child->relationship();

        switch ($pieces->count()) {
            case 2:
                return $relationship->properties()->contains((string) $pieces->get(1));

            case 3:
                $subPiece = (string) $pieces->get(1);

                if (!$relationship->childProperty() === $subPiece) {
                    return false;
                }

                return $child->properties()->contains((string) $pieces->get(2));
        }

        return false;
    }
}
