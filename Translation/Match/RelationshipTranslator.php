<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslatorInterface,
    Metadata\EntityInterface,
    IdentityMatch
};
use Innmind\Neo4j\DBAL\{
    Query,
    Clause\Expression\Relationship
};
use Innmind\Immutable\Map;

class RelationshipTranslator implements MatchTranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function translate(EntityInterface $meta): IdentityMatch
    {
        $query = (new Query)
            ->match('start')
            ->linkedTo('end')
            ->through(
                (string) $meta->type(),
                'entity',
                Relationship::RIGHT
            )
            ->return('start', 'end', 'entity');


        return new IdentityMatch(
            $query,
            (new Map('string', EntityInterface::class))
                ->put('entity', $meta)
        );
    }
}
