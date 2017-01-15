<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    MetadataFactory\AggregateFactory,
    MetadataFactory\RelationshipFactory,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    Collection,
    Map
};
use Symfony\Component\Config\Definition\{
    ConfigurationInterface,
    Processor
};

class MetadataBuilder
{
    private $metadatas;
    private $factories;
    private $config;
    private $processor;

    public function __construct(
        Types $types,
        Map $factories = null,
        ConfigurationInterface $config = null
    ) {
        $this->metadatas = new Metadatas;
        $this->factories = $factories ?? (new Map('string', MetadataFactoryInterface::class))
            ->put('aggregate', new AggregateFactory($types))
            ->put('relationship', new RelationshipFactory($types));
        $this->config = $config ?? new Configuration;
        $this->processor = new Processor;

        if (
            (string) $this->factories->keyType() !== 'string' ||
            (string) $this->factories->valueType() !== MetadataFactoryInterface::class
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
            $this->metadatas->register(
                $this->build($class, $meta)
            );
        }

        return $this;
    }

    /**
     * Build an entity metadata
     *
     * @param string $class
     * @param array $config
     *
     * @return EntityInterface
     */
    public function build(string $class, array $config): EntityInterface
    {
        $config = (new Collection($config))
            ->set('class', $class);

        return $this
            ->factories
            ->get($config->get('type'))
            ->make($config->unset('type'));
    }
}