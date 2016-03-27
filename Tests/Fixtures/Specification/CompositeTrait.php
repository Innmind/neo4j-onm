<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Fixtures\Specification;

use Innmind\Specification\{
    SpecificationInterface,
    CompositeInterface,
    NotInterface,
    Operator
};

trait CompositeTrait
{
    public function and(SpecificationInterface $spec): CompositeInterface
    {
        return new Composite($this, $spec, Operator::AND);
    }

    public function or(SpecificationInterface $spec): CompositeInterface
    {
        return new Composite($this, $spec, Operator::OR);
    }

    public function not(): NotInterface
    {
        return new Not($this);
    }
}
