<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Identity,
    Exception\SpecificationNotApplicableAsPropertyMatch,
    Query\PropertiesMatch,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Operator,
    Sign,
};
use Innmind\Immutable\{
    Map,
    Str,
};

final class RelationshipVisitor implements PropertyMatchVisitor
{
    private Relationship $meta;

    public function __construct(Relationship $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(Specification $specification): Map
    {
        switch (true) {
            case $specification instanceof Comparator:
                if (!$specification->sign()->equals(Sign::equality())) {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->buildMapping($specification);

            case $specification instanceof Composite:
                if (!$specification->operator()->equals(Operator::and())) {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->merge(
                    ($this)($specification->left()),
                    ($this)($specification->right())
                );
        }

        throw new SpecificationNotApplicableAsPropertyMatch;
    }

    private function buildMapping(
        Comparator $specification
    ): Map {
        $property = $specification->property();

        switch (true) {
            case $this->meta->properties()->contains($property):
                return $this->buildPropertyMapping($specification);

            case $this->meta->startNode()->property() === $property:
                return $this->buildEdgeMapping(
                    $specification,
                    $this->meta->startNode(),
                    'start'
                );

            case $this->meta->endNode()->property() === $property:
                return $this->buildEdgeMapping(
                    $specification,
                    $this->meta->endNode(),
                    'end'
                );
        }
    }

    private function buildPropertyMapping(
        Comparator $specification
    ): Map {
        $prop = $specification->property();
        $key = Str::of('entity_')->append($prop);

        return Map::of('string', PropertiesMatch::class)
            (
                'entity',
                new PropertiesMatch(
                    Map::of('string', 'string')
                        ($prop, $key->prepend('{')->append('}')->toString()),
                    Map::of('string', 'mixed')
                        ($key->toString(), $specification->value())
                )
            );
    }

    private function buildEdgeMapping(
        Comparator $specification,
        RelationshipEdge $edge,
        string $side
    ): Map {
        $key = Str::of($side)
            ->append('_')
            ->append($edge->target());
        $value = $specification->value();

        if ($value instanceof Identity) {
            $value = $value->value();
        }

        return Map::of('string', PropertiesMatch::class)
            (
                $side,
                new PropertiesMatch(
                    Map::of('string', 'string')
                        (
                            $edge->target(),
                            $key
                                ->prepend('{')
                                ->append('}')
                                ->toString()
                        ),
                    Map::of('string', 'mixed')
                        ($key->toString(), $value)
                )
            );
    }

    private function merge(
        Map $left,
        Map $right
    ): Map {
        return $right->reduce(
            $left,
            static function(Map $carry, string $var, PropertiesMatch $data) use ($left): Map {
                if (!$carry->contains($var)) {
                    return $carry->put($var, $data);
                }

                return $carry->put(
                    $var,
                    $data->merge($left->get($var))
                );
            }
        );
    }
}
