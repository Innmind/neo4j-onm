<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

/**
 * Entity alias
 */
class Alias
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
