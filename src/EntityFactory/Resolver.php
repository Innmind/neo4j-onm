<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory as EntityFactoryInterface,
    Metadata\Entity,
};
use Innmind\Immutable\Map;

final class Resolver
{
    private $mapping;

    public function __construct(EntityFactoryInterface ...$factories)
    {
        $this->mapping = new Map('string', EntityFactoryInterface::class);

        foreach ($factories as $factory) {
            $this->register($factory);
        }
    }

    /**
     * Return the factory for the given entity definition
     */
    public function __invoke(Entity $meta): EntityFactoryInterface
    {
        $class = (string) $meta->factory();

        if ($this->mapping->contains($class)) {
            return $this->mapping->get($class);
        }

        $factory = new $class;
        $this->register($factory);

        return $factory;
    }

    /**
     * Register the given entity factory instance
     */
    private function register(EntityFactoryInterface $factory): self
    {
        $this->mapping = $this->mapping->put(
            get_class($factory),
            $factory
        );

        return $this;
    }
}
