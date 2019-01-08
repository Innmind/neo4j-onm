<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Specification\Specification;

interface Validator
{
    /**
     * Check if the given specification is applicable for the given entity definition
     */
    public function __invoke(
        Specification $specification,
        Entity $meta
    ): bool;
}
