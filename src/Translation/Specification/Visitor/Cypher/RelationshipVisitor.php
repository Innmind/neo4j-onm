<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Identity,
    Query\Where,
    Specification\ConvertSign,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class RelationshipVisitor implements CypherVisitor
{
    private Relationship $meta;
    private ConvertSign $convert;
    private int $count = 0;

    public function __construct(Relationship $meta)
    {
        $this->meta = $meta;
        $this->convert = new ConvertSign;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(Specification $specification): Where
    {
        switch (true) {
            case $specification instanceof Comparator:
                ++$this->count; //used for parameters name, so a same property can be used multiple times

                return $this->buildCondition($specification);

            case $specification instanceof Composite:
                $left = ($this)($specification->left());
                $right = ($this)($specification->right());
                $operator = (string) Str::of((string) $specification->operator())->toLower();

                return $left->{$operator}($right);

            case $specification instanceof Not:
                return ($this)($specification->specification())->not();
        }
    }

    private function buildCondition(Comparator $specification): Where
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
        Comparator $specification
    ): Where {
        $prop = $specification->property();
        $key = Str::of('entity_')
            ->append($prop)
            ->append((string) $this->count);

        return new Where(
            \sprintf(
                'entity.%s %s %s',
                $prop,
                ($this->convert)($specification->sign()),
                $key->prepend('{')->append('}')
            ),
            Map::of('string', 'mixed')
                ((string) $key, $specification->value())
        );
    }

    private function buildEdgeCondition(
        Comparator $specification,
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
                ($this->convert)($specification->sign()),
                $key->prepend('{')->append('}')
            ),
            Map::of('string', 'mixed')
                ((string) $key, $value)
        );
    }
}
