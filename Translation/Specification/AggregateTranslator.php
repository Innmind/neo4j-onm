<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslatorInterface,
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor as AggregatePropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\AggregateVisitor as AggregateCypherVisitor,
    Metadata\ValueObject,
    Metadata\EntityInterface,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\{
    Map,
    StringPrimitive as Str,
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

            $variables = new Set('string');
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
                    &$query
                ) {
                    $name = (new Str('entity_'))->append($property);
                    $query = $query
                        ->match('entity')
                        ->linkedTo(
                            (string) $name
                                ->append('_')
                                ->append($child->relationship()->childProperty()),
                            $child->labels()->toPrimitive()
                        )
                        ->through(
                            (string) $child->relationship()->type(),
                            (string) $name,
                            Relationship::LEFT
                        );
                });
            $query = $query->where(
                (new AggregateCypherVisitor($meta))->visit($specification)
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
            $query = $query
                ->withProperties(
                    $mapping->get($name)->get(0)->toPrimitive()
                )
                ->withParameters(
                    $mapping->get($name)->get(1)->toPrimitive()
                );
        }

        return $query;
    }
}
