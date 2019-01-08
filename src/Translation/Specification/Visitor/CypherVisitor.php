<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation\Specification\Visitor;

use Innmind\Neo4j\ONM\Query\Where;
use Innmind\Specification\Specification;

interface CypherVisitor
{
    /**
     * Return a cypher string to be used in a where clause along with the parameters
     */
    public function __invoke(Specification $specification): Where;
}
