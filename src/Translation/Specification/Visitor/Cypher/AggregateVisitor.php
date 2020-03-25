<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Aggregate,
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
use function Innmind\Immutable\join;

final class AggregateVisitor implements CypherVisitor
{
    private Aggregate $meta;
    private ConvertSign $convert;
    private int $count = 0;

    public function __construct(Aggregate $meta)
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
        $property = Str::of($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyCondition($specification);

            case $property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyCondition($specification);

            default:
                throw new LogicException("Unknown property '{$property->toString()}'");
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
                $key->prepend('{')->append('}')->toString(),
            ),
            Map::of('string', 'mixed')
                ($key->toString(), $specification->value()),
        );
    }

    private function buildSubPropertyCondition(Comparator $specification): Where
    {
        $prop = Str::of($specification->property());
        $pieces = $prop->split('.');
        $var = Str::of('entity_')->append(
            join(
                '_',
                $pieces->dropEnd(1)->mapTo(
                    'string',
                    static fn(Str $piece): string => $piece->toString(),
                ),
            )->toString(),
        );
        $key = $var
            ->append('_')
            ->append($pieces->last()->toString())
            ->append((string) $this->count);

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
        return new Where(
            \sprintf(
                '%s %s %s',
                $var
                    ->append('.')
                    ->append($pieces->last()->toString())
                    ->toString(),
                ($this->convert)($specification->sign()),
                $key->prepend('{')->append('}')->toString(),
            ),
            Map::of('string', 'mixed')
                ($key->toString(), $specification->value()),
        );
    }
}
