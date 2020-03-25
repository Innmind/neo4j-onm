<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor;

use Innmind\Neo4j\ONM\{
    Query\PropertiesMatch,
    Exception\SpecificationNotApplicableAsPropertyMatch,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Map;

interface PropertyMatchVisitor
{
    /**
     * Return a map composed of the property map and the associated parameters
     *
     * @throws SpecificationNotApplicableAsPropertyMatch
     *
     * @return Map<string, PropertiesMatch>
     */
    public function __invoke(Specification $specification): Map;
}
