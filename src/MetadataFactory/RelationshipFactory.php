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
    Metadata\Alias,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Repository as EntityRepository,
    EntityFactory\RelationshipFactory as EntityFactory,
    Types,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class RelationshipFactory implements MetadataFactory
{
    private $types;

    public function __construct(Types $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function make(MapInterface $config): Entity
    {
        if (
            (string) $config->keyType() !== 'string' ||
            (string) $config->valueType() !== 'mixed'
        ) {
            throw new InvalidArgumentException;
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
            new Factory(
                $config->contains('factory') ?
                    $config->get('factory') : EntityFactory::class
            ),
            new Alias(
                $config->contains('alias') ?
                    $config->get('alias') : $config->get('class')
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
                    $this->types->build(
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
        $map = new Map('string', 'mixed');

        foreach ($data as $key => $value) {
            $map = $map->put($key, $value);
        }

        return $map;
    }
}
