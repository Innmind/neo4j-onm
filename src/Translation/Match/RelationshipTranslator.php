<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Metadata\Entity,
    Metadata\Relationship,
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
        if (!$meta instanceof Relationship) {
            throw new \TypeError('Argument 1 must be of type '.Relationship::class);
        }

        $query = (new Query)
            ->match('start')
            ->linkedTo('end')
            ->through(
                (string) $meta->type(),
                'entity',
                'right'
            )
            ->return('start', 'end', 'entity');


        /** @psalm-suppress InvalidArgument */
        return new IdentityMatch(
            $query,
            Map::of('string', Entity::class)
                ('entity', $meta)
        );
    }
}
