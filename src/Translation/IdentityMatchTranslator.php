<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\Entity,
    Identity,
    IdentityMatch,
};

interface IdentityMatchTranslator
{
    /**
     * Translate an identity into a query to match the expected element
     */
    public function __invoke(
        Entity $meta,
        Identity $identity
    ): IdentityMatch;
}
