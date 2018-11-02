<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\Entity,
    IdentityMatch,
};

interface MatchTranslator
{
    /**
     * Use an entity metadata to build a query to match all entities
     */
    public function __invoke(Entity $meta): IdentityMatch;
}
