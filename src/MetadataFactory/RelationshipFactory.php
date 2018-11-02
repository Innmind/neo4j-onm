<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Repository\Repository as EntityRepository,
    Types,
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

        $entity = new Relationship(
            new ClassName($config->get('class')),
            new Identity(
                $config->get('identity')['property'],
                $config->get('identity')['type']
            ),
            new Repository(
                $config->contains('repository') ?
                    $config->get('repository') : EntityRepository::class
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
            )
        );

        if ($config->contains('properties')) {
            $entity = $this->appendProperties(
                $entity,
                $this->map($config->get('properties'))
            );
        }

        return $entity;
    }

    private function appendProperties(
        Relationship $relationship,
        MapInterface $properties
    ): Relationship {
        return $properties->reduce(
            $relationship,
            function(Relationship $carry, string $name, array $config): Relationship {
                $config = $this->map($config);

                return $carry->withProperty(
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
