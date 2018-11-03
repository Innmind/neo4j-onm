<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Entity,
    Metadata\Aggregate\Child,
    IdentityMatch,
};
use Innmind\Neo4j\DBAL\{
    Query\Query,
    Clause\Expression\Relationship,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class AggregateTranslator implements MatchTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Entity $meta): IdentityMatch
    {
        $query = (new Query)
            ->match(
                'entity',
                $meta->labels()->toPrimitive()
            )
            ->with('entity');

        $variables = new Set('string');
        $meta
            ->children()
            ->foreach(function(
                string $property,
                Child $child
            ) use (
                &$query,
                &$variables
            ) {
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


        return new IdentityMatch(
            $query->return('entity', ...$variables->toPrimitive()),
            Map::of('string', Entity::class)
                ('entity', $meta)
        );
    }
}
