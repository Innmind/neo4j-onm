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
    /** @var Map<string, EntityFactoryInterface> */
    private Map $mapping;

    public function __construct(EntityFactoryInterface ...$factories)
    {
        /** @var Map<string, EntityFactoryInterface> */
        $this->mapping = Map::of('string', EntityFactoryInterface::class);

        foreach ($factories as $factory) {
            $this->register($factory);
        }
    }

    /**
     * Return the factory for the given entity definition
     */
    public function __invoke(Entity $meta): EntityFactoryInterface
    {
        $class = $meta->factory()->toString();

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
    private function register(EntityFactoryInterface $factory): void
    {
        $this->mapping = ($this->mapping)(
            \get_class($factory),
            $factory,
        );
    }
}
