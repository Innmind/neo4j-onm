<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

class ClassName
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function __toString(): string
    {
        return $this->class;
    }
}
