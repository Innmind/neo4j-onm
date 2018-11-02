<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor as AggregatePropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\AggregateVisitor as AggregateCypherVisitor,
    Metadata\ValueObject,
    Metadata\Entity,
    IdentityMatch,
    Exception\SpecificationNotApplicableAsPropertyMatch,
};
use Innmind\Neo4j\DBAL\{
    Query\Query,
    Clause\Expression\Relationship,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
    Str,
};
use Innmind\Specification\SpecificationInterface;

final class AggregateTranslator implements SpecificationTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(
        Entity $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        $variables = new Set('string');

        try {
            $mapping = (new AggregatePropertyMatchVisitor($meta))($specification);

            $query = $this
                ->addProperties(
                    (new Query)->match(
                        'entity',
                        $meta->labels()->toPrimitive()
                    ),
                    'entity',
                    $mapping
                )
                ->with('entity');

            $meta
                ->children()
                ->foreach(function(
                    string $property,
                    ValueObject $child
                ) use (
                    &$query,
                    $mapping,
                    &$variables
                ): void {
                    $relName = Str::of('entity_')->append($property);
                    $childName = $relName
                        ->append('_')
                        ->append($child->relationship()->childProperty());
                    $variables = $variables
                        ->add((string) $relName)
                        ->add((string) $childName);

                    $query = $this->addProperties(
                        $this
                            ->addProperties(
                                $query
                                    ->match('entity')
                                    ->linkedTo(
                                        (string) $childName,
                                        $child->labels()->toPrimitive()
                                    ),
                                (string) $childName,
                                $mapping
                            )
                            ->through(
                                (string) $child->relationship()->type(),
                                (string) $relName,
                                Relationship::LEFT
                            ),
                        (string) $relName,
                        $mapping
                    );
                });
        } catch (SpecificationNotApplicableAsPropertyMatch $e) {
            $query = (new Query)
                ->match(
                    'entity',
                    $meta->labels()->toPrimitive()
                )
                ->with('entity');

            $meta
                ->children()
                ->foreach(function(
                    string $property,
                    ValueObject $child
                ) use (
                    &$query,
                    &$variables
                ): void {
                    $relName = Str::of('entity_')->append($property);
                    $childName = $relName
                        ->append('_')
                        ->append($child->relationship()->childProperty());
                    $variables = $variables
                        ->add((string) $relName)
                        ->add((string) $childName);

                    $query = $query
                        ->match('entity')
                        ->linkedTo(
                            (string) $childName,
                            $child->labels()->toPrimitive()
                        )
                        ->through(
                            (string) $child->relationship()->type(),
                            (string) $relName,
                            Relationship::LEFT
                        );
                });
            $condition = (new AggregateCypherVisitor($meta))($specification);
            $query = $query->where($condition->cypher());
            $query = $condition->parameters()->reduce(
                $query,
                static function(Query $query, string $key, $value): Query {
                    return $query->withParameter($key, $value);
                }
            );
        }

        return new IdentityMatch(
            $query->return('entity', ...$variables->toPrimitive()),
            Map::of('string', Entity::class)
                ('entity', $meta)
        );
    }

    /**
     * @param MapInterface<string, PropertiesMatch> $mapping
     */
    private function addProperties(
        Query $query,
        string $name,
        MapInterface $mapping
    ): Query {
        if ($mapping->contains($name)) {
            $match = $mapping->get($name);
            $query = $match->properties()->reduce(
                $query,
                static function(Query $query, string $property, string $cypher): Query {
                    return $query->withProperty($property, $cypher);
                }
            );
            $query = $match->parameters()->reduce(
                $query,
                static function(Query $query, string $key, $value): Query {
                    return $query->withParameter($key, $value);
                }
            );
        }

        return $query;
    }
}
