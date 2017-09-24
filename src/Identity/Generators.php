<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Generators
{
    private $mapping;

    public function __construct(MapInterface $mapping = null)
    {
        $mapping = $mapping ?? new Map('string', Generator::class);
        $this->mapping = (new Map('string', Generator::class))
            ->put(Uuid::class, new Generator\UuidGenerator)
            ->merge($mapping);
    }

    /**
     * Return the generator for the given identity class
     */
    public function get(string $class): Generator
    {
        return $this->mapping->get($class);
    }
}
