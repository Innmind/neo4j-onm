<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\{
    Comparator,
    Sign,
};

class Property implements Comparator
{
    use CompositeTrait;

    private $property;
    private $sign;
    private $value;

    public function __construct(string $property, Sign $sign, $value)
    {
        $this->property = $property;
        $this->sign = $sign;
        $this->value = $value;
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    public function value()
    {
        return $this->value;
    }
}
