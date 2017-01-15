<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor;

use Innmind\Specification\SpecificationInterface;
use Innmind\Immutable\SequenceInterface;

interface CypherVisitorInterface
{
    /**
     * Return a cypher string to be used in a where clause along with the parameters
     *
     * @param SpecificationInterface $specification
     *
     * @return SequenceInterface
     */
    public function visit(SpecificationInterface $specification): SequenceInterface;
}
