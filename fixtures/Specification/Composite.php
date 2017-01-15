<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    SpecificationInterface,
    Operator,
    CompositeInterface
};

class Composite implements CompositeInterface
{
    use CompositeTrait;

    private $left;
    private $right;
    private $operator;

    public function __construct(
        SpecificationInterface $left,
        SpecificationInterface $right,
        string $operator
    ) {
        $this->left = $left;
        $this->right = $right;
        $this->operator = new Operator($operator);
    }

    public function left(): SpecificationInterface
    {
        return $this->left;
    }

    public function right(): SpecificationInterface
    {
        return $this->right;
    }

    public function operator(): Operator
    {
        return $this->operator;
    }
}
