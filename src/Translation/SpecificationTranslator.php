<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\Entity,
    IdentityMatch,
};
use Innmind\Specification\Specification;

interface SpecificationTranslator
{
    /**
     * Translate a specification into a query to match the expected elements
     */
    public function __invoke(
        Entity $meta,
        Specification $specification
    ): IdentityMatch;
}
