<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Metadatas
{
    private $aliases;
    private $mapping;

    public function __construct(EntityInterface ...$metas)
    {
        $this->aliases = new Map('string', 'string');
        $this->mapping = new Map('string', EntityInterface::class);

        foreach ($metas as $meta) {
            $this->register($meta);
        }
    }

    /**
     * Register a new entity metadata
     *
     * @param EntityInterface $meta
     *
     * @return self
     */
    private function register(EntityInterface $meta): self
    {
        $this->aliases = $this->aliases->put(
            (string) $meta->alias(),
            (string) $meta->class()
        );
        $this->mapping = $this->mapping->put((string) $meta->class(), $meta);

        return $this;
    }

    /**
     * Return the metadata for an entity
     *
     * @param string $name Class or alias
     *
     * @return EntityInterface
     */
    public function get(string $name): EntityInterface
    {
        if ($this->aliases->contains($name)) {
            $name = $this->aliases->get($name);
        }

        return $this->mapping->get($name);
    }
}
