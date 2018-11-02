<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadata\Entity,
    MetadataFactory\AggregateFactory,
    MetadataFactory\RelationshipFactory,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use Symfony\Component\Config\Definition\{
    ConfigurationInterface,
    Processor,
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
        $this->factories = $factories ?? Map::of('string', MetadataFactory::class)
            ('aggregate', new AggregateFactory($types))
            ('relationship', new RelationshipFactory($types));
        $this->config = $config ?? new Configuration;
        $this->processor = new Processor;

        if (
            (string) $this->factories->keyType() !== 'string' ||
            (string) $this->factories->valueType() !== MetadataFactory::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                MetadataFactory::class
            ));
        }
    }

    public function container(): Metadatas
    {
        return $this->metadatas ?? $this->metadatas = new Metadatas(...$this->definitions);
    }

    /**
     * Append the given mapping to the metadatas
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
        return Map::of(
            'string',
            'mixed',
            array_keys($data),
            array_values($data)
        );
    }
}
