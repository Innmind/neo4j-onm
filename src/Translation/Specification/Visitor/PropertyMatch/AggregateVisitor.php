<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitorInterface,
    Metadata\Aggregate,
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

class AggregateVisitor implements PropertyMatchVisitorInterface
{
    private $meta;

    public function __construct(Aggregate $meta)
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
        $property = new Str($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyMapping($specification);

            case $property->match('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyMapping($specification);
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

    private function buildSubPropertyMapping(
        ComparatorInterface $specification
    ): MapInterface {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = (new Str('entity_'))->append($pieces->pop()->join('_'));
        $key = $var->append('_')->append((string) $pieces->last());

        return (new Map('string', SequenceInterface::class))
            ->put(
                (string) $var,
                new Sequence(
                    new Collection([
                        (string) $pieces->last() => (string) $key
                            ->prepend('{')
                            ->append('}'),
                    ]),
                    new Collection([
                        (string) $key => $specification->value()
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
