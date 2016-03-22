<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Immutable\{
    Map,
    MapInterface
};

class Generators
{
    private $mapping;

    public function __construct()
    {
        $this->mapping = (new Map('string', GeneratorInterface::class))
            ->put(Uuid::class, new Generator\UuidGenerator);
    }

    /**
     * Reference the couple identity class / generator
     *
     * @param string $class
     * @param GeneratorInterface $generator
     *
     * @return self
     */
    public function register(string $class, GeneratorInterface $generator): self
    {
        $this->mapping = $this->mapping->put($class, $generator);

        return $this;
    }

    /**
     * Return the generator for the given identity class
     *
     * @param string $class
     *
     * @return GeneratorInterface
     */
    public function get(string $class): GeneratorInterface
    {
        return $this->mapping->get($class);
    }

    /**
     * Return the whole mapping
     *
     * @return MapInterface<string, GeneratorInterface>
     */
    public function all(): MapInterface
    {
        return $this->mapping;
    }
}
