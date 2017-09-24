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
    Exception\SpecificationNotApplicableAsPropertyMatchException
};
use Innmind\Neo4j\DBAL\{
    Query\Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\{
    Map,
    Str,
    MapInterface,
    Set
};
use Innmind\Specification\SpecificationInterface;

final class AggregateTranslator implements SpecificationTranslator
{
    /**
     * {@inheritdoc}
     */
    public function translate(
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
                ) {
                    $relName = (new Str('entity_'))->append($property);
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
        } catch (SpecificationNotApplicableAsPropertyMatchException $e) {
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
                ) {
                    $relName = (new Str('entity_'))->append($property);
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
            $query = $query
                ->where($condition->cypher())
                ->withParameters(
                    $condition
                        ->parameters()
                        ->reduce(
                            [],
                            function(array $carry, string $key, $value): array {
                                $carry[$key] = $value;

                                return $carry;
                            }
                        )
                );
        }

        return new IdentityMatch(
            $query->return('entity', ...$variables->toPrimitive()),
            (new Map('string', Entity::class))
                ->put('entity', $meta)
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
            $query = $query
                ->withProperties(
                    $mapping
                        ->get($name)
                        ->properties()
                        ->reduce(
                            [],
                            function(array $carry, string $property, string $cypher): array {
                                $carry[$property] = $cypher;

                                return $carry;
                            }
                        )
                )
                ->withParameters(
                    $mapping
                        ->get($name)
                        ->parameters()
                        ->reduce(
                            [],
                            function(array $carry, string $key, $value): array {
                                $carry[$key] = $value;

                                return $carry;
                            }
                        )
                );
        }

        return $query;
    }
}
