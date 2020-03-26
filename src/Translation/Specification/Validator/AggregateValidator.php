<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Validator;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator,
    Metadata\Entity,
    Metadata\Aggregate,
    Exception\InvalidArgumentException,
};
use Innmind\Specification\{
    Comparator,
    Composite,
    Not,
    Specification,
};
use Innmind\Immutable\Str;

final class AggregateValidator implements Validator
{
    public function __invoke(
        Specification $specification,
        Entity $meta
    ): bool {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        switch (true) {
            case $specification instanceof Comparator:
                return $this->isValidProperty(
                    $specification->property(),
                    $meta,
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

    private function isValidProperty(string $property, Aggregate $meta): bool
    {
        if ($meta->properties()->contains($property)) {
            return true;
        }

        $property = Str::of($property);

        if (!$property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/')) {
            return false;
        }

        $pieces = $property->split('.');
        $piece = $pieces->get(0)->toString();

        if (!$meta->children()->contains($piece)) {
            return false;
        }

        $child = $meta->children()->get($piece);
        $relationship = $child->relationship();

        switch ($pieces->count()) {
            case 2:
                return $relationship->properties()->contains($pieces->get(1)->toString());

            case 3:
                $subPiece = $pieces->get(1)->toString();

                if ($relationship->childProperty() !== $subPiece) {
                    return false;
                }

                return $child->properties()->contains($pieces->get(2)->toString());
        }

        return false;
    }
}
