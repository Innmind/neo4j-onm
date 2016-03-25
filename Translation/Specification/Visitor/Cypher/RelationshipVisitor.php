<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\CypherVisitorInterface,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    IdentityInterface,
    Exception\SpecificationNotApplicableAsPropertyMatchException
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    NotInterface
};
use Innmind\Immutable\{
    MapInterface,
    StringPrimitive as Str,
    SequenceInterface,
    Sequence,
    Map,
    Collection
};

class RelationshipVisitor implements CypherVisitorInterface
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

    private function buildEdgeCondition(
        ComparatorInterface $specification,
        RelationshipEdge $edge,
        string $side
    ): SequenceInterface {
        $key = (new Str($side))
            ->append('_')
            ->append($edge->target())
            ->append((string) $this->count);
        $value = $specification->value();

        if ($value instanceof IdentityInterface) {
            $value = $value->value();
        }

        return new Sequence(
            sprintf(
                '%s.%s %s %s',
                $side,
                $edge->target(),
                $specification->sign(),
                $key->prepend('{')->append('}')
            ),
            new Collection([
                (string) $key => $value,
            ])
        );
    }
}
