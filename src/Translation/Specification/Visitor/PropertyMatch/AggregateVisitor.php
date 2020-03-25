<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Aggregate,
    Exception\SpecificationNotApplicableAsPropertyMatch,
    Query\PropertiesMatch,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Operator,
    Sign,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class AggregateVisitor implements PropertyMatchVisitor
{
    private Aggregate $meta;

    public function __construct(Aggregate $meta)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdo}
     */
    public function __invoke(Specification $specification): MapInterface
    {
        switch (true) {
            case $specification instanceof Comparator:
                if (!$specification->sign()->equals(Sign::equality())) {
                    throw new SpecificationNotApplicableAsPropertyMatch;
                }

                return $this->buildMapping($specification);

            case $specification instanceof Composite:
                if (!$specification->operator()->equals(Operator::and())) {
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
        Comparator $specification
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
        Comparator $specification
    ): MapInterface {
        $prop = $specification->property();
        $key = Str::of('entity_')->append($prop);

        return Map::of('string', PropertiesMatch::class)
            (
                'entity',
                new PropertiesMatch(
                    Map::of('string', 'string')
                        ($prop, (string) $key->prepend('{')->append('}')),
                    Map::of('string', 'mixed')
                        ((string) $key, $specification->value())
                )
            );
    }

    private function buildSubPropertyMapping(
        Comparator $specification
    ): MapInterface {
        $prop = new Str($specification->property());
        $pieces = $prop->split('.');
        $var = Str::of('entity_')->append(
            (string) $pieces->dropEnd(1)->join('_')
        );
        $key = $var->append('_')->append((string) $pieces->last());

        return Map::of('string', PropertiesMatch::class)
            (
                (string) $var,
                new PropertiesMatch(
                    Map::of('string', 'string')
                        (
                            (string) $pieces->last(),
                            (string) $key
                                ->prepend('{')
                                ->append('}')
                        ),
                    Map::of('string', 'mixed')
                        ((string) $key, $specification->value())
                )
            );
    }

    private function merge(
        MapInterface $left,
        MapInterface $right
    ): MapInterface {
        return $right->reduce(
            $left,
            static function(MapInterface $carry, string $var, PropertiesMatch $data) use ($left): MapInterface {
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
