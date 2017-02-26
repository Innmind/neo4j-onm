<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslatorInterface,
    Translation\Specification\Visitor\PropertyMatch\RelationshipVisitor as RelationshipPropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\RelationshipVisitor as RelationshipCypherVisitor,
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
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

final class RelationshipTranslator implements SpecificationTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        EntityInterface $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        try {
            $mapping = (new RelationshipPropertyMatchVisitor($meta))->visit(
                $specification
            );

            $query = $this->addProperties(
                $this
                    ->addProperties(
                        $this
                            ->addProperties(
                                (new Query)->match('start'),
                                'start',
                                $mapping
                            )
                            ->linkedTo('end'),
                        'end',
                        $mapping
                    )
                    ->through(
                        (string) $meta->type(),
                        'entity',
                        Relationship::RIGHT
                    ),
                'entity',
                $mapping
            );
        } catch (SpecificationNotApplicableAsPropertyMatchException $e) {
            $condition = (new RelationshipCypherVisitor($meta))->visit(
                $specification
            );
            $query = (new Query)
                ->match('start')
                ->linkedTo('end')
                ->through(
                    (string) $meta->type(),
                    'entity',
                    Relationship::RIGHT
                )
                ->where($condition->first());
            $query = $condition
                ->last()
                ->reduce(
                    $query,
                    function(Query $carry, string $name, $value): Query {
                        return $carry->withParameter($name, $value);
                    }
                );
        }

        return new IdentityMatch(
            $query->return('start', 'end', 'entity'),
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
