<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Translation\Specification\Visitor\PropertyMatch\RelationshipVisitor as RelationshipPropertyMatchVisitor,
    Translation\Specification\Visitor\Cypher\RelationshipVisitor as RelationshipCypherVisitor,
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
};
use Innmind\Specification\SpecificationInterface;

final class RelationshipTranslator implements SpecificationTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(
        Entity $meta,
        SpecificationInterface $specification
    ): IdentityMatch {
        try {
            $mapping = (new RelationshipPropertyMatchVisitor($meta))($specification);

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
        } catch (SpecificationNotApplicableAsPropertyMatch $e) {
            $condition = (new RelationshipCypherVisitor($meta))($specification);
            $query = (new Query)
                ->match('start')
                ->linkedTo('end')
                ->through(
                    (string) $meta->type(),
                    'entity',
                    Relationship::RIGHT
                )
                ->where($condition->cypher());
            $query = $condition->parameters()->reduce(
                $query,
                static function(Query $query, string $key, $value): Query {
                    return $query->withParameter($key, $value);
                }
            );
        }

        return new IdentityMatch(
            $query->return('start', 'end', 'entity'),
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
