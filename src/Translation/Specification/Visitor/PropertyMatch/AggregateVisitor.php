<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Aggregate,
    Exception\SpecificationNotApplicableAsPropertyMatch,
    Exception\LogicException,
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

    /**
     * @return Map<string, PropertiesMatch>
     */
    private function buildMapping(Comparator $specification): Map
    {
        $property = Str::of($specification->property());

        switch (true) {
            case $this->meta->properties()->contains($specification->property()):
                return $this->buildPropertyMapping($specification);

            case $property->matches('/[a-zA-Z]+(\.[a-zA-Z]+)+/'):
                return $this->buildSubPropertyMapping($specification);

            default:
                throw new LogicException("Unknown property '{$property->toString()}'");
        }
    }

    /**
     * @return Map<string, PropertiesMatch>
     */
    private function buildPropertyMapping(Comparator $specification): Map
    {
        $prop = $specification->property();
        $key = Str::of('entity_')->append($prop);

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
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

    /**
     * @return Map<string, PropertiesMatch>
     */
    private function buildSubPropertyMapping(Comparator $specification): Map
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
        $key = $var->append('_')->append($pieces->last()->toString());

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
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

    /**
     * @param Map<string, PropertiesMatch> $left
     * @param Map<string, PropertiesMatch> $right
     *
     * @return Map<string, PropertiesMatch>
     */
    private function merge(Map $left, Map $right): Map
    {
        /** @var Map<string, PropertiesMatch> */
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
