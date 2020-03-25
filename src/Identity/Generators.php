<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\Identity;
use Innmind\Immutable\Map;

final class Generators
{
    private Map $mapping;

    public function __construct(Map $mapping = null)
    {
        $mapping = $mapping ?? Map::of('string', Generator::class);
        $this->mapping = Map::of('string', Generator::class)
            (Uuid::class, new Generator\UuidGenerator)
            ->merge($mapping);
    }

    /**
     * Return the generator for the given identity class
     */
    public function get(string $class): Generator
    {
        return $this->mapping->get($class);
    }

    public function new(string $class): Identity
    {
        return $this->get($class)->new();
    }
}
