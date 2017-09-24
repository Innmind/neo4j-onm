<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadata\Entity,
    MetadataFactory\AggregateFactory,
    MetadataFactory\RelationshipFactory,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set
};
use Symfony\Component\Config\Definition\{
    ConfigurationInterface,
    Processor
};

final class MetadataBuilder
{
    private $definitions;
    private $metadatas;
    private $factories;
    private $config;
    private $processor;

    public function __construct(
        Types $types,
        MapInterface $factories = null,
        ConfigurationInterface $config = null
    ) {
        $this->definitions = new Set(Entity::class);
        $this->factories = $factories ?? (new Map('string', MetadataFactory::class))
            ->put('aggregate', new AggregateFactory($types))
            ->put('relationship', new RelationshipFactory($types));
        $this->config = $config ?? new Configuration;
        $this->processor = new Processor;

        if (
            (string) $this->factories->keyType() !== 'string' ||
            (string) $this->factories->valueType() !== MetadataFactory::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * Return the metadatas container
     *
     * @return Metadatas
     */
    public function container(): Metadatas
    {
        if (!$this->metadatas instanceof Metadatas) {
            $this->metadatas = new Metadatas(...$this->definitions);
        }

        return $this->metadatas;
    }

    /**
     * Append the given mapping to the metadatas
     *
     * @param array $metas
     *
     * @return self
     */
    public function inject(array $metas): self
    {
        $metas = $this->processor->processConfiguration(
            $this->config,
            $metas
        );

        foreach ($metas as $class => $meta) {
            $this->definitions = $this->definitions->add(
                $this->build(
                    $class,
                    $this->map($meta)
                )
            );
        }

        return $this;
    }

    /**
     * Build an entity metadata
     *
     * @param MapInterface<string, mixed> $config
     */
    public function build(string $class, MapInterface $config): Entity
    {
        $config = $config->put('class', $class);

        return $this
            ->factories
            ->get($config->get('type'))
            ->make($config->remove('type'));
    }

    /**
     * @return MapInterface<string, mixed>
     */
    private function map(array $data): MapInterface
    {
        $map = new Map('string', 'mixed');

        foreach ($data as $key => $value) {
            $map = $map->put($key, $value);
        }

        return $map;
    }
}
