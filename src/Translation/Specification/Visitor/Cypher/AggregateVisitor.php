<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitorInterface,
    Metadata\Aggregate,
    Exception\SpecificationNotApplicableAsPropertyMatchException,
    Query\Where
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    NotInterface
};
use Innmind\Immutable\{
    Str,
    Map
};

final class AggregateVisitor implements CypherVisitorInterface
{
    private $meta;
    private $count = 0;

    public function __construct(Aggregate $meta)
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
                $operator = strtolower((string) $specification->operator());

                return $left->{$operator}($right);

            case $specification instanceof NotInterface:
                return ($this)($specification->specification())->not();
        }
    }

    private function buildCondition(ComparatorInterface $specification): Where
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
        ComparatorInterface $specification
    ): Where {
        $prop = $specification->property();
        $key = (new Str('entity_'))
            ->append($prop)
            ->append((string) $this->count);

        return new Where(
            sprintf(
                'entity.%s %s %s',
                $prop,
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            (new Map('string', 'mixed'))
                ->put((string) $key, $specification->value())
        );
    }

    private function buildSubPropertyCondition(
        ComparatorInterface $specification
    ): Where {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = (new Str('entity_'))->append(
            (string) $pieces->dropEnd(1)->join('_')
        );
        $key = $var
            ->append('_')
            ->append((string) $pieces->last())
            ->append((string) $this->count);

        return new Where(
            sprintf(
                '%s %s %s',
                $var
                    ->append('.')
                    ->append((string) $pieces->last()),
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            (new Map('string', 'mixed'))
                ->put((string) $key, $specification->value())
        );
    }
}
