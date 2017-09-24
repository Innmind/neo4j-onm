<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslator,
    Identity,
    Metadata\Entity,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query\Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\Map;

final class RelationshipTranslator implements IdentityMatchTranslator
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        Entity $meta,
        Identity $identity
    ): IdentityMatch {
        $query = (new Query)
            ->match('start')
            ->linkedTo('end')
            ->through(
                (string) $meta->type(),
                'entity',
                Relationship::RIGHT
            )
            ->withProperty(
                $meta->identity()->property(),
                '{entity_identity}'
            )
            ->withParameter('entity_identity', $identity->value())
            ->return('start', 'end', 'entity');


        return new IdentityMatch(
            $query,
            (new Map('string', Entity::class))
                ->put('entity', $meta)
        );
    }
}
