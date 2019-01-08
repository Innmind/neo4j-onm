<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor;

use Innmind\Specification\Specification;
use Innmind\Immutable\MapInterface;

interface PropertyMatchVisitor
{
    /**
     * Return a map composed of the property map and the associated parameters
     *
     * @throws SpecificationNotApplicableAsPropertyMatchException
     *
     * @return MapInterface<string, PropertiesMatch>
     */
    public function __invoke(Specification $specification): MapInterface;
}
