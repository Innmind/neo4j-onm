<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;

/**
 * Holds the repository class for an entity
 */
final class Repository
{
    private $class;

    public function __construct(string $class)
    {
        if (empty($class)) {
            throw new DomainException;
        }

        $this->class = $class;
    }

    public function __toString(): string
    {
        return $this->class;
    }
}
