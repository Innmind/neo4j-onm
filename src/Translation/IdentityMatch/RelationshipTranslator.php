<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslator,
    Identity,
    Metadata\Entity,
    Metadata\Relationship,
    IdentityMatch,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\Map;

final class RelationshipTranslator implements IdentityMatchTranslator
{
    public function __invoke(
        Entity $meta,
        Identity $identity
    ): IdentityMatch {
        if (!$meta instanceof Relationship) {
            throw new \TypeError('Argument 1 must be of type '.Relationship::class);
        }

        $query = (new Query)
            ->match('start')
            ->linkedTo('end')
            ->through(
                $meta->type()->toString(),
                'entity',
                'right',
            )
            ->withProperty(
                $meta->identity()->property(),
                '{entity_identity}',
            )
            ->withParameter('entity_identity', $identity->value())
            ->return('start', 'end', 'entity');


        /** @psalm-suppress InvalidArgument */
        return new IdentityMatch(
            $query,
            Map::of('string', Entity::class)
                ('entity', $meta),
        );
    }
}
