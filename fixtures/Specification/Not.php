<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    NotInterface,
    SpecificationInterface
};

class Not implements NotInterface
{
    use CompositeTrait;

    private $specification;

    public function __construct(SpecificationInterface $specification)
    {
        $this->specification = $specification;
    }

    public function specification(): SpecificationInterface
    {
        return $this->specification;
    }
}
