<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatchTranslatorInterface,
    IdentityInterface,
    Metadata\EntityInterface,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\Map;

final class RelationshipTranslator implements IdentityMatchTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(
        EntityInterface $meta,
        IdentityInterface $identity
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
            (new Map('string', EntityInterface::class))
                ->put('entity', $meta)
        );
    }
}
