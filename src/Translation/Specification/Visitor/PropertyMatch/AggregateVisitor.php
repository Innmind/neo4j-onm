<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Aggregate,
    Exception\SpecificationNotApplicableAsPropertyMatch,
    Query\PropertiesMatch
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
    Map
};

final class AggregateVisitor implements PropertyMatchVisitor
{
    private $meta;

    public function __construct(Aggregate $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(SpecificationInterface $specification): MapInterface
    {
        switch (true) {
            case $specification instanceof ComparatorInterface:
                if ($specification->sign() !== '=') {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->buildMapping($specification);

            case $specification instanceof CompositeInterface:
                if ((string) $specification->operator() !== Operator::AND) {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->merge(
                    ($this)($specification->left()),
                    ($this)($specification->right())
                );
        }

        throw new SpecificationNotApplicableAsPropertyMatch;
    }

    private function buildMapping(
        ComparatorInterface $specification
    ): MapInterface {
        $property = new Str($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyMapping($specification);

            case $property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyMapping($specification);
        }
    }

    private function buildPropertyMapping(
        ComparatorInterface $specification
    ): MapInterface {
        $prop = $specification->property();
        $key = (new Str('entity_'))->append($prop);

        return (new Map('string', PropertiesMatch::class))
            ->put(
                'entity',
                new PropertiesMatch(
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

    private function buildSubPropertyMapping(
        ComparatorInterface $specification
    ): MapInterface {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = (new Str('entity_'))->append(
            (string) $pieces->dropEnd(1)->join('_')
        );
        $key = $var->append('_')->append((string) $pieces->last());

        return (new Map('string', PropertiesMatch::class))
            ->put(
                (string) $var,
                new PropertiesMatch(
                    (new Map('string', 'string'))
                        ->put(
                            (string) $pieces->last(),
                            (string) $key
                                ->prepend('{')
                                ->append('}')
                        ),
                    (new Map('string', 'mixed'))
                        ->put((string) $key, $specification->value())
                )
            );
    }

    private function merge(
        MapInterface $left,
        MapInterface $right
    ): MapInterface {
        return $right->reduce(
            $left,
            function(MapInterface $carry, string $var, PropertiesMatch $data) use ($left): MapInterface {
                if (!$carry->contains($var)) {
                    return $carry->put($var, $data);
                }

                return $carry->put(
                    $var,
                    $data->merge($left->get($var))
                );
            }
        );
    }
}
