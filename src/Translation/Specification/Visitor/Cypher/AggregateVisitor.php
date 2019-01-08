<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Aggregate,
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
    Map,
    Str,
};

final class AggregateVisitor implements CypherVisitor
{
    private $meta;
    private $convert;
    private $count = 0;

    public function __construct(Aggregate $meta)
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
        $property = new Str($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyCondition($specification);

            case $property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyCondition($specification);
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

    private function buildSubPropertyCondition(
        Comparator $specification
    ): Where {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = Str::of('entity_')->append(
            (string) $pieces->dropEnd(1)->join('_')
        );
        $key = $var
            ->append('_')
            ->append((string) $pieces->last())
            ->append((string) $this->count);

        return new Where(
            \sprintf(
                '%s %s %s',
                $var
                    ->append('.')
                    ->append((string) $pieces->last()),
                ($this->convert)($specification->sign()),
                $key->prepend('{')->append('}')
            ),
            Map::of('string', 'mixed')
                ((string) $key, $specification->value())
        );
    }
}
