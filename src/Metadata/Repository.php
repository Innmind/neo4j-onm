<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\InvalidArgumentException;

/**
 * Holds the repository class for an entity
 */
final class Repository
{
    private $class;

    public function __construct(string $class)
    {
        if (empty($class)) {
            throw new InvalidArgumentException;
        }

        $this->class = $class;
    }

    public function __toString(): string
    {
        return $this->class;
    }
}
