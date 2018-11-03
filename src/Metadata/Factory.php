<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\Str;

/**
 * Holds the class name for an entity factory
 */
final class Factory
{
    private $class;

    public function __construct(string $class)
    {
        if (Str::of($class)->empty()) {
            throw new DomainException;
        }

        $this->class = $class;
    }

    public function __toString(): string
    {
        return $this->class;
    }
}
