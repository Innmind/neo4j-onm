<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    Not as NotInterface,
    Specification
};

class Not implements NotInterface
{
    use CompositeTrait;

    private $specification;

    public function __construct(Specification $specification)
    {
        $this->specification = $specification;
    }

    public function specification(): Specification
    {
        return $this->specification;
    }
}
