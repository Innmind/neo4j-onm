<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    Specification,
    Composite as CompositeInterface,
    Not as NotInterface,
    Operator,
};

trait CompositeTrait
{
    public function and(Specification $spec): CompositeInterface
    {
        return new Composite($this, $spec, Operator::and());
    }

    public function or(Specification $spec): CompositeInterface
    {
        return new Composite($this, $spec, Operator::or());
    }

    public function not(): NotInterface
    {
        return new Not($this);
    }
}
