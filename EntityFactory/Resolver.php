<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactoryInterface,
    Metadata\EntityInterface
};
use Innmind\Immutable\Map;

class Resolver
{
    private $mapping;

    public function __construct()
    {
        $this->mapping = new Map('string', EntityFactoryInterface::class);
    }

    /**
     * Add the given entity factory instance
     *
     * @param EntityFactoryInterface $factory
     *
     * @return self
     */
    public function add(EntityFactoryInterface $factory): self
    {
        $this->mapping = $this->mapping->put(
            get_class($factory),
            $factory
        );

        return $this;
    }

    /**
     * Return the factory for the given entity definition
     *
     * @param EntityInterface $meta
     *
     * @return EntityFactoryInterface
     */
    public function get(EntityInterface $meta): EntityFactoryInterface
    {
        $class = (string) $meta->factory();

        if ($this->mapping->contains($class)) {
            return $this->mapping->get($class);
        }

        $factory = new $class;
        $this->add($factory);

        return $factory;
    }
}
