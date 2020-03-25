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
    Map,
    Str,
};
use function Innmind\Immutable\join;

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
    public function __invoke(Specification $specification): Map
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
    ): Map {
        $property = Str::of($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyMapping($specification);

            case $property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyMapping($specification);
        }
    }

    private function buildPropertyMapping(
        Comparator $specification
    ): Map {
        $prop = $specification->property();
        $key = Str::of('entity_')->append($prop);

        return Map::of('string', PropertiesMatch::class)
            (
                'entity',
                new PropertiesMatch(
                    Map::of('string', 'string')
                        ($prop, $key->prepend('{')->append('}')->toString()),
                    Map::of('string', 'mixed')
                        ($key->toString(), $specification->value())
                )
            );
    }

    private function buildSubPropertyMapping(
        Comparator $specification
    ): Map {
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
        $key = $var->append('_')->append($pieces->last()->toString());

        return Map::of('string', PropertiesMatch::class)
            (
                $var->toString(),
                new PropertiesMatch(
                    Map::of('string', 'string')
                        (
                            $pieces->last()->toString(),
                            $key
                                ->prepend('{')
                                ->append('}')
                                ->toString(),
                        ),
                    Map::of('string', 'mixed')
                        ($key->toString(), $specification->value())
                )
            );
    }

    private function merge(
        Map $left,
        Map $right
    ): Map {
        return $right->reduce(
            $left,
            static function(Map $carry, string $var, PropertiesMatch $data) use ($left): Map {
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
