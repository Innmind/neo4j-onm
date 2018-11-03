<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Factory,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Types,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class RelationshipFactory implements MetadataFactory
{
    private $build;

    public function __construct(Types $build)
    {
        $this->build = $build;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(MapInterface $config): Entity
    {
        if (
            (string) $config->keyType() !== 'string' ||
            (string) $config->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $entity = Relationship::of(
            new ClassName($config->get('class')),
            new Identity(
                $config->get('identity')['property'],
                $config->get('identity')['type']
            ),
            new RelationshipType($config->get('rel_type')),
            new RelationshipEdge(
                $config->get('startNode')['property'],
                $config->get('startNode')['type'],
                $config->get('startNode')['target']
            ),
            new RelationshipEdge(
                $config->get('endNode')['property'],
                $config->get('endNode')['type'],
                $config->get('endNode')['target']
            ),
            $this->properties($this->map($config['properties'] ?? []))
        );

        return $entity;
    }

    private function properties(MapInterface $properties): MapInterface
    {
        return $properties->reduce(
            Map::of('string', Type::class),
            function(MapInterface $properties, string $name, array $config): MapInterface {
                $config = $this->map($config);

                return $properties->put(
                    $name,
                    ($this->build)(
                        $config->get('type'),
                        $config
                    )
                );
            }
        );
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
