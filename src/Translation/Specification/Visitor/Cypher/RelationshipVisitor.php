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
    Exception\LogicException,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
};
use Innmind\Immutable\{
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

    public function __invoke(Specification $specification): Where
    {
        switch (true) {
            case $specification instanceof Comparator:
                ++$this->count; //used for parameters name, so a same property can be used multiple times

                return $this->buildCondition($specification);

            case $specification instanceof Composite:
                $left = ($this)($specification->left());
                $right = ($this)($specification->right());
                $operator = Str::of((string) $specification->operator())->toLower()->toString();

                /** @var Where */
                return $left->{$operator}($right);

            case $specification instanceof Not:
                return ($this)($specification->specification())->not();

            default:
                $class = \get_class($specification);

                throw new LogicException("Unknown specification '$class'");
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
                    'start',
                );

            case $this->meta->endNode()->property() === $property:
                return $this->buildEdgeCondition(
                    $specification,
                    $this->meta->endNode(),
                    'end',
                );

            default:
                throw new LogicException("Unknown property '$property'");
        }
    }

    private function buildPropertyCondition(Comparator $specification): Where
    {
        $prop = $specification->property();
        $key = Str::of('entity_')
            ->append($prop)
            ->append((string) $this->count);

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
        return new Where(
            \sprintf(
                'entity.%s %s %s',
                $prop,
                ($this->convert)($specification->sign()),
                $key->prepend('$')->toString(),
            ),
            Map::of('string', 'mixed')
                ($key->toString(), $specification->value()),
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
        /** @var mixed */
        $value = $specification->value();

        if ($value instanceof Identity) {
            /** @var mixed */
            $value = $value->value();
        }

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
        return new Where(
            \sprintf(
                '%s.%s %s %s',
                $side,
                $edge->target(),
                ($this->convert)($specification->sign()),
                $key->prepend('$')->toString(),
            ),
            Map::of('string', 'mixed')
                ($key->toString(), $value),
        );
    }
}
