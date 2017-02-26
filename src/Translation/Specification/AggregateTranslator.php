<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslatorInterface,
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor as AggregatePropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\AggregateVisitor as AggregateCypherVisitor,
    Metadata\ValueObject,
    Metadata\EntityInterface,
    IdentityMatch,
    Exception\SpecificationNotApplicableAsPropertyMatchException
};
use Innmind\Neo4j\DBAL\{
    Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\{
    Map,
    Str,
    MapInterface,
    Set
};
use Innmind\Specification\SpecificationInterface;

class AggregateTranslator implements SpecificationTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        EntityInterface $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        $variables = new Set('string');

        try {
            $mapping = (new AggregatePropertyMatchVisitor($meta))->visit(
                $specification
            );

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
            $condition = (new AggregateCypherVisitor($meta))->visit(
                $specification
            );
            $query = $query->where($condition->first());
            $query = $condition
                ->last()
                ->reduce(
                    $query,
                    function(Query $carry, string $key, $value): Query {
                        return $carry->withParameter($key, $value);
                    }
                );
        }

        return new IdentityMatch(
            $query->return('entity', ...$variables->toPrimitive()),
            (new Map('string', EntityInterface::class))
                ->put('entity', $meta)
        );
    }

    private function addProperties(
        Query $query,
        string $name,
        MapInterface $mapping
    ): Query {
        if ($mapping->contains($name)) {
            $query = $mapping
                ->get($name)
                ->first()
                ->reduce(
                    $query,
                    function(Query $carry, string $property, string $cypher): Query {
                        return $carry->withProperty($property, $cypher);
                    }
                );
            $query = $mapping
                ->get($name)
                ->last()
                ->reduce(
                    $query,
                    function(Query $carry, string $key, $value): Query {
                        return $carry->withParameter($key, $value);
                    }
                );
        }

        return $query;
    }
}
