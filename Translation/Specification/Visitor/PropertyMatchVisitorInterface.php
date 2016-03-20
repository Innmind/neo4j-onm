<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor;

use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\MapInterface;

interface PropertyMatchVisitorInterface
{
    /**
     * Return a map composed of the property map and the associated parameters
     *
     * @param SpecificationInterface $specification
     *
     * @throws SpecificationNotApplicableAsPropertyMatchException
     *
     * @return MapInterface<string, SequenceInterface>
     */
    public function visit(SpecificationInterface $specification): MapInterface;
}
