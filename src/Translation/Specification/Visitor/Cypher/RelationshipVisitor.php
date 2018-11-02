<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Identity,
    Query\Where,
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    NotInterface,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class RelationshipVisitor implements CypherVisitor
{
    private $meta;
    private $count = 0;

    public function __construct(Relationship $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(SpecificationInterface $specification): Where
    {
        switch (true) {
            case $specification instanceof ComparatorInterface:
                ++$this->count; //used for parameters name, so a same property can be used multiple times

                return $this->buildCondition($specification);

            case $specification instanceof CompositeInterface:
                $left = ($this)($specification->left());
                $right = ($this)($specification->right());
                $operator = (string) Str::of((string) $specification->operator())->toLower();

                return $left->{$operator}($right);

            case $specification instanceof NotInterface:
                return ($this)($specification->specification())->not();
        }
    }

    private function buildCondition(ComparatorInterface $specification): Where
    {
        $property = $specification->property();

        switch (true) {
            case $this->meta->properties()->contains($property):
                return $this->buildPropertyCondition($specification);

            case $this->meta->startNode()->property() === $property:
                return $this->buildEdgeCondition(
                    $specification,
                    $this->meta->startNode(),
                    'start'
                );

            case $this->meta->endNode()->property() === $property:
                return $this->buildEdgeCondition(
                    $specification,
                    $this->meta->endNode(),
                    'end'
                );
        }
    }

    private function buildPropertyCondition(
        ComparatorInterface $specification
    ): Where {
        $prop = $specification->property();
        $key = Str::of('entity_')
            ->append($prop)
            ->append((string) $this->count);

        return new Where(
            \sprintf(
                'entity.%s %s %s',
                $prop,
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            Map::of('string', 'mixed')
                ((string) $key, $specification->value())
        );
    }

    private function buildEdgeCondition(
        ComparatorInterface $specification,
        RelationshipEdge $edge,
        string $side
    ): Where {
        $key = Str::of($side)
            ->append('_')
            ->append($edge->target())
            ->append((string) $this->count);
        $value = $specification->value();

        if ($value instanceof Identity) {
            $value = $value->value();
        }

        return new Where(
            \sprintf(
                '%s.%s %s %s',
                $side,
                $edge->target(),
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            Map::of('string', 'mixed')
                ((string) $key, $value)
        );
    }
}
