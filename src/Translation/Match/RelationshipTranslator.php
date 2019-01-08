<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Entity,
    IdentityMatch,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\Map;

final class RelationshipTranslator implements MatchTranslator
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Entity $meta): IdentityMatch
    {
        $query = (new Query)
            ->match('start')
            ->linkedTo('end')
            ->through(
                (string) $meta->type(),
                'entity',
                'right'
            )
            ->return('start', 'end', 'entity');


        return new IdentityMatch(
            $query,
            Map::of('string', Entity::class)
                ('entity', $meta)
        );
    }
}
