<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    IdentityMatch
};

interface MatchTranslatorInterface
{
    /**
     * Use an entity metadata to build a query to match all entities
     *
     * @param EntityInterface $meta
     *
     * @return IdentityMatch
     */
    public function translate(EntityInterface $meta): IdentityMatch;
}
