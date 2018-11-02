<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory,
    Metadata\Entity,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Repository\Repository as EntityRepository,
    EntityFactory\AggregateFactory as EntityFactory,
    Types,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class AggregateFactory implements MetadataFactory
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
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $entity = new Aggregate(
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
            $config->get('labels')
        );

        if ($config->contains('properties')) {
            $entity = $this->appendProperties(
                $entity,
                $this->map(
                    $config->get('properties')
                )
            );
        }

        if ($config->contains('children')) {
            $entity = $this->appendChildren(
                $entity,
                $this->map(
                    $config->get('children')
                )
            );
        }

        return $entity;
    }

    private function appendProperties(
        object $object,
        MapInterface $properties
    ): object {
        return $properties->reduce(
            $object,
            function(object $carry, string $name, array $config): object {
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

    private function appendChildren(
        Aggregate $entity,
        MapInterface $children
    ): Aggregate {
        return $children->reduce(
            $entity,
            function(Aggregate $carry, string $name, array $config): Aggregate {
                $config = $this->map($config);
                $rel = new ValueObjectRelationship(
                    new ClassName($config->get('class')),
                    new RelationshipType($config->get('type')),
                    $name,
                    $config->get('child')['property'],
                    $config->contains('collection') ?
                        (bool) $config->get('collection') : false
                );

                if ($config->contains('properties')) {
                    $rel = $this->appendProperties(
                        $rel,
                        $this->map($config->get('properties'))
                    );
                }

                $config = $this->map($config->get('child'));
                $child = new ValueObject(
                    new ClassName($config->get('class')),
                    $config->get('labels'),
                    $rel
                );

                if ($config->contains('properties')) {
                    $child = $this->appendProperties(
                        $child,
                        $this->map($config->get('properties'))
                    );
                }

                return $carry->withChild($child);
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
