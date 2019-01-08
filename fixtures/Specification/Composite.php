<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    Specification,
    Operator,
    Composite as CompositeInterface,
};

class Composite implements CompositeInterface
{
    use CompositeTrait;

    private $left;
    private $right;
    private $operator;

    public function __construct(
        Specification $left,
        Specification $right,
        Operator $operator
    ) {
        $this->left = $left;
        $this->right = $right;
        $this->operator = $operator;
    }

    public function left(): Specification
    {
        return $this->left;
    }

    public function right(): Specification
    {
        return $this->right;
    }

    public function operator(): Operator
    {
        return $this->operator;
    }
}
