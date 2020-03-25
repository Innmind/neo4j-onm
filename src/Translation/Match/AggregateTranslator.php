<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Entity,
    Metadata\Aggregate\Child,
    IdentityMatch,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;

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
                ...unwrap($meta->labels()),
            )
            ->with('entity');

        $variables = Set::strings();
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
                    ->add($relName->toString())
                    ->add($childName->toString());

                $query = $query
                    ->match('entity')
                    ->linkedTo(
                        $childName->toString(),
                        ...unwrap($child->labels()),
                    )
                    ->through(
                        (string) $child->relationship()->type(),
                        $relName->toString(),
                        'left'
                    );
            });


        return new IdentityMatch(
            $query->return('entity', ...unwrap($variables)),
            Map::of('string', Entity::class)
                ('entity', $meta)
        );
    }
}
