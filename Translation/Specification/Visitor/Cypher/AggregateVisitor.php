<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitorInterface,
    Metadata\Aggregate,
    Exception\SpecificationNotApplicableAsPropertyMatchException
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    NotInterface
};
use Innmind\Immutable\{
    StringPrimitive as Str,
    SequenceInterface,
    Sequence,
    Collection
};

class AggregateVisitor implements CypherVisitorInterface
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
    public function visit(
        SpecificationInterface $specification
    ): SequenceInterface {

        switch (true) {
            case $specification instanceof ComparatorInterface:
                ++$this->count; //used for parameters name, so a same property can be used multiple times

                return $this->buildCondition($specification);

            case $specification instanceof CompositeInterface:
                $left = $this->visit($specification->left());
                $right = $this->visit($specification->right());

                return new Sequence(
                    sprintf(
                        '%s %s %s',
                        $left->get(0),
                        $specification->operator(),
                        $right->get(0)
                    ),
                    $left->get(1)->merge($right->get(1))
                );

            case $specification instanceof NotInterface:
                $condition = $this->visit($specification->specification());

                return new Sequence(
                    sprintf(
                        'NOT (%s)',
                        $condition->get(0)
                    ),
                    $condition->get(1)
                );
        }
    }

    private function buildCondition(
        ComparatorInterface $specification
    ): SequenceInterface {
        $property = new Str($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyCondition($specification);

            case $property->match('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyCondition($specification);
        }
    }

    private function buildPropertyCondition(
        ComparatorInterface $specification
    ): SequenceInterface {
        $prop = $specification->property();
        $key = (new Str('entity_'))
            ->append($prop)
            ->append((string) $this->count);

        return new Sequence(
            sprintf(
                'entity.%s %s %s',
                $prop,
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            new Collection([
                (string) $key => $specification->value(),
            ])
        );
    }

    private function buildSubPropertyCondition(
        ComparatorInterface $specification
    ): SequenceInterface {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = (new Str('entity_'))->append($pieces->pop()->join('_'));
        $key = $var
            ->append('_')
            ->append((string) $pieces->last())
            ->append((string) $this->count);

        return new Sequence(
            sprintf(
                '%s %s %s',
                $var
                    ->append('.')
                    ->append((string) $pieces->last()),
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            new Collection([
                (string) $key => $specification->value(),
            ])
        );
    }
}
