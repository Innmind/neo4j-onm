<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

/**
 * Holds the property name of an entity identifier
 */
class Identity
{
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function __toString(): string
    {
        return $this->property;
    }
}
