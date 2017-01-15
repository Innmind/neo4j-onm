<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\ComparatorInterface;

class Property implements ComparatorInterface
{
    use CompositeTrait;

    private $property;
    private $sign;
    private $value;

    public function __construct(string $property, string $sign, $value)
    {
        $this->property = $property;
        $this->sign = $sign;
        $this->value = $value;
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): string
    {
        return $this->sign;
    }

    public function value()
    {
        return $this->value;
    }
}
