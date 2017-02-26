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
    Str,
    SequenceInterface,
    Sequence,
    Map
};

final class RelationshipVisitor implements PropertyMatchVisitorInterface
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
                    (new Map('string', 'string'))
                        ->put(
                            $prop,
                            (string) $key
                                ->prepend('{')
                                ->append('}')
                        ),
                    (new Map('string', 'mixed'))
                        ->put((string) $key, $specification->value())
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
                    (new Map('string', 'string'))
                        ->put(
                            $edge->target(),
                            (string) $key
                                ->prepend('{')
                                ->append('}')
                        ),
                    (new Map('string', 'mixed'))
                        ->put((string) $key, $value)
                )
            );
    }

    private function merge(
        MapInterface $left,
        MapInterface $right
    ): MapInterface {
        return $right->reduce(
            $left,
            function(MapInterface $carry, string $var, SequenceInterface $data) use ($left): MapInterface {
                if (!$carry->contains($var)) {
                    return $carry->put($var, $data);
                }

                return $carry->put(
                    $var,
                    new Sequence(
                        $data->first()->merge($left->get($var)->first()),
                        $data->last()->merge($left->get($var)->last())
                    )
                );
            }
        );
    }
}
