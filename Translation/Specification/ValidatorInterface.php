<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Specification\SpecificationInterface;

interface ValidatorInterface
{
    /**
     * Check if the given specification is applicable for the given entity definition
     *
     * @param SpecificationInterface $specification
     * @param EntityInterface $meta
     *
     * @return bool
     */
    public function validate(
        SpecificationInterface $specification,
        EntityInterface $meta
    ): bool;
}
