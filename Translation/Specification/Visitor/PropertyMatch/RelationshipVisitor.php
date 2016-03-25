<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitorInterface,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    IdentityInterface,
    Exception\SpecificationNotApplicableAsPropertyMatchException
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    Operator
};
use Innmind\Immutable\{
    MapInterface,
    StringPrimitive as Str,
    SequenceInterface,
    Sequence,
    Map,
    Collection
};

class RelationshipVisitor implements PropertyMatchVisitorInterface
{
    private $meta;

    public function __construct(Relationship $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function visit(SpecificationInterface $specification): MapInterface
    {
        switch (true) {
            case $specification instanceof ComparatorInterface:
                if ($specification->sign() !== '=') {
                    throw new SpecificationNotApplicableAsPropertyMatchException;
                }

                return $this->buildMapping($specification);

            case $specification instanceof CompositeInterface:
                if ((string) $specification->operator() !== Operator::AND) {
                    throw new SpecificationNotApplicableAsPropertyMatchException;
                }

                return $this->merge(
                    $this->visit($specification->left()),
                    $this->visit($specification->right())
                );
        }

        throw new SpecificationNotApplicableAsPropertyMatchException;
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
        $key = (new Str('entity_'))->append($prop);

        return (new Map('string', SequenceInterface::class))
            ->put(
                'entity',
                new Sequence(
                    new Collection([
                        $prop => (string) $key
                            ->prepend('{')
                            ->append('}'),
                    ]),
                    new Collection([
                        (string) $key => $specification->value()
                    ])
                )
            );
    }

    private function buildEdgeMapping(
        ComparatorInterface $specification,
        RelationshipEdge $edge,
        string $side
    ): MapInterface {
        $key = (new Str($side))
            ->append('_')
            ->append($edge->target());
        $value = $specification->value();

        if ($value instanceof IdentityInterface) {
            $value = $value->value();
        }

        return (new Map('string', SequenceInterface::class))
            ->put(
                $side,
                new Sequence(
                    new Collection([
                        $edge->target() => (string) $key
                            ->prepend('{')
                            ->append('}'),
                    ]),
                    new Collection([
                        (string) $key => $value,
                    ])
                )
            );
    }

    private function merge(
        MapInterface $left,
        MapInterface $right
    ): MapInterface {
        $map = $left;
        $right->foreach(function(
            string $var,
            SequenceInterface $data
        ) use (
            &$map,
            $left
        ) {
            if (!$map->contains($var)) {
                $map = $map->put($var, $data);

                return;
            }

            $map = $map->put(
                $var,
                new Sequence(
                    $data->get(0)->merge($left->get($var)->get(0)),
                    $data->get(1)->merge($left->get($var)->get(1))
                )
            );
        });

        return $map;
    }
}
