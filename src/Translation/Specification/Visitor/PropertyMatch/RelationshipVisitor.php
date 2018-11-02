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
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    Operator,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class RelationshipVisitor implements PropertyMatchVisitor
{
    private $meta;

    public function __construct(Relationship $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(SpecificationInterface $specification): MapInterface
    {
        switch (true) {
            case $specification instanceof ComparatorInterface:
                if ($specification->sign() !== '=') {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->buildMapping($specification);

            case $specification instanceof CompositeInterface:
                if ((string) $specification->operator() !== Operator::AND) {
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
        ComparatorInterface $specification
    ): MapInterface {
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
        ComparatorInterface $specification
    ): MapInterface {
        $prop = $specification->property();
        $key = Str::of('entity_')->append($prop);

        return Map::of('string', PropertiesMatch::class)
            (
                'entity',
                new PropertiesMatch(
                    Map::of('string', 'string')
                        ($prop, (string) $key->prepend('{')->append('}')),
                    Map::of('string', 'mixed')
                        ((string) $key, $specification->value())
                )
            );
    }

    private function buildEdgeMapping(
        ComparatorInterface $specification,
        RelationshipEdge $edge,
        string $side
    ): MapInterface {
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
                            (string) $key
                                ->prepend('{')
                                ->append('}')
                        ),
                    Map::of('string', 'mixed')
                        ((string) $key, $value)
                )
            );
    }

    private function merge(
        MapInterface $left,
        MapInterface $right
    ): MapInterface {
        return $right->reduce(
            $left,
            static function(MapInterface $carry, string $var, PropertiesMatch $data) use ($left): MapInterface {
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
