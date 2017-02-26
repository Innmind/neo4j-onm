<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

/**
 * Holds the repository class for an entity
 */
final class Repository
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
