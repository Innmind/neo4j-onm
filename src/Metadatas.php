<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Immutable\Map;

final class Metadatas
{
    /** @var Map<string, Entity> */
    private Map $mapping;

    public function __construct(Entity ...$metas)
    {
        /** @var Map<string, Entity> */
        $this->mapping = Map::of('string', Entity::class);

        foreach ($metas as $meta) {
            $this->register($meta);
        }
    }

    /**
     * Return the metadata for an entity
     */
    public function __invoke(string $class): Entity
    {
        return $this->mapping->get($class);
    }

    /**
     * Register a new entity metadata
     */
    private function register(Entity $meta): void
    {
        $this->mapping = ($this->mapping)($meta->class()->toString(), $meta);
    }
}
