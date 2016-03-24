<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    IdentityMatch
};
use Innmind\Specification\SpecificationInterface;

interface SpecificationTranslatorInterface
{
    /**
     * Translate a specification into a query to match the expected elements
     *
     * @param EntityInterface $meta
     * @param SpecificationInterface $specification
     *
     * @return IdentityMatch
     */
    public function translate(
        EntityInterface $meta,
        SpecificationInterface $specification
    ): IdentityMatch;
}
