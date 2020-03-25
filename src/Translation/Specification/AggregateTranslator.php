<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor as AggregatePropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\AggregateVisitor as AggregateCypherVisitor,
    Metadata\Aggregate\Child,
    Metadata\Entity,
    Metadata\Aggregate,
    IdentityMatch,
    Query\PropertiesMatch,
    Exception\SpecificationNotApplicableAsPropertyMatch,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;
use Innmind\Specification\Specification;

final class AggregateTranslator implements SpecificationTranslator
{
    /** @var Set<string> */
    private Set $variables;

    public function __construct()
    {
        $this->variables = Set::strings();
    }

    public function __invoke(
        Entity $meta,
        Specification $specification
    ): IdentityMatch {
        if (!$meta instanceof Aggregate) {
            throw new \TypeError('Argument 1 must be of type '.Aggregate::class);
        }

        $this->variables = $this->variables->clear();

        try {
            $mapping = (new AggregatePropertyMatchVisitor($meta))($specification);

            $query = $this
                ->addProperties(
                    (new Query)->match(
                        'entity',
                        ...unwrap($meta->labels()),
                    ),
                    'entity',
                    $mapping,
                )
                ->with('entity');

            $query = $meta->children()->reduce(
                $query,
                function(
                    Query $query,
                    string $property,
                    Child $child
                ) use (
                    $mapping
                ): Query {
                    $relName = Str::of('entity_')->append($property);
                    $childName = $relName
                        ->append('_')
                        ->append($child->relationship()->childProperty());
                    $this->variables = $this->variables
                        ->add($relName->toString())
                        ->add($childName->toString());

                    return $this->addProperties(
                        $this
                            ->addProperties(
                                $query
                                    ->match('entity')
                                    ->linkedTo(
                                        $childName->toString(),
                                        ...unwrap($child->labels()),
                                    ),
                                $childName->toString(),
                                $mapping,
                            )
                            ->through(
                                $child->relationship()->type()->toString(),
                                $relName->toString(),
                                'left',
                            ),
                        $relName->toString(),
                        $mapping,
                    );
                });
        } catch (SpecificationNotApplicableAsPropertyMatch $e) {
            $query = (new Query)
                ->match(
                    'entity',
                    ...unwrap($meta->labels()),
                )
                ->with('entity');

            $query = $meta->children()->reduce(
                $query,
                function(
                    Query $query,
                    string $property,
                    Child $child
                ): Query {
                    $relName = Str::of('entity_')->append($property);
                    $childName = $relName
                        ->append('_')
                        ->append($child->relationship()->childProperty());
                    $this->variables = ($this->variables)
                        ($relName->toString())
                        ($childName->toString());

                    return $query
                        ->match('entity')
                        ->linkedTo(
                            $childName->toString(),
                            ...unwrap($child->labels()),
                        )
                        ->through(
                            $child->relationship()->type()->toString(),
                            $relName->toString(),
                            'left',
                        );
                });
            $condition = (new AggregateCypherVisitor($meta))($specification);
            $query = $query->where($condition->cypher());
            $query = $condition->parameters()->reduce(
                $query,
                static function(Query $query, string $key, $value): Query {
                    return $query->withParameter($key, $value);
                },
            );
        }

        $variables = $this->variables;
        $this->variables = $this->variables->clear();

        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return new IdentityMatch(
            $query->return('entity', ...unwrap($variables)),
            Map::of('string', Entity::class)
                ('entity', $meta),
        );
    }

    /**
     * @param Map<string, PropertiesMatch> $mapping
     */
    private function addProperties(
        Query $query,
        string $name,
        Map $mapping
    ): Query {
        if ($mapping->contains($name)) {
            $match = $mapping->get($name);
            $query = $match->properties()->reduce(
                $query,
                static function(Query $query, string $property, string $cypher): Query {
                    return $query->withProperty($property, $cypher);
                },
            );
            $query = $match->parameters()->reduce(
                $query,
                static function(Query $query, string $key, $value): Query {
                    return $query->withParameter($key, $value);
                },
            );
        }

        return $query;
    }
}
