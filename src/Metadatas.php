<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Metadatas
{
    private $aliases;
    private $mapping;

    public function __construct(Entity ...$metas)
    {
        $this->aliases = new Map('string', 'string');
        $this->mapping = new Map('string', Entity::class);

        foreach ($metas as $meta) {
            $this->register($meta);
        }
    }

    /**
     * Register a new entity metadata
     *
     * @return self
     */
    private function register(Entity $meta): self
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
     */
    public function get(string $name): Entity
    {
        if ($this->aliases->contains($name)) {
            $name = $this->aliases->get($name);
        }

        return $this->mapping->get($name);
    }
}
