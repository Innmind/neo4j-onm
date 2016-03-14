<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactoryInterface,
    Metadata\EntityInterface,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Repository as EntityRepository,
    EntityFactory\AggregateFactory as EntityFactory,
    Types
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class AggregateFactory implements MetadataFactoryInterface
{
    private $types;

    public function __construct(Types $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function make(CollectionInterface $config): EntityInterface
    {
        $entity = new Aggregate(
            new ClassName($config->get('class')),
            new Identity(
                $config->get('identity')['property'],
                $config->get('identity')['type']
            ),
            new Repository(
                $config->hasKey('repository') ?
                    $config->get('repository') : EntityRepository::class
            ),
            new Factory(
                $config->hasKey('factory') ?
                    $config->get('factory') : EntityFactory::class
            ),
            new Alias(
                $config->hasKey('alias') ?
                    $config->get('alias') : $config->get('class')
            ),
            $config->get('labels')
        );

        if ($config->hasKey('properties')) {
            $entity = $this->appendProperties(
                $entity,
                new Collection($config->get('properties'))
            );
        }

        if ($config->hasKey('children')) {
            $entity = $this->appendChildren(
                $entity,
                new Collection($config->get('children'))
            );
        }

        return $entity;
    }

    private function appendProperties(
        $object,
        CollectionInterface $properties
    ) {
        $properties->each(function(string $name, array $config) use (&$object) {
            $object = $object->withProperty(
                $name,
                $this->types->build(
                    $config['type'],
                    new Collection($config)
                )
            );
        });

        return $object;
    }

    private function appendChildren(
        Aggregate $entity,
        CollectionInterface $children
    ): Aggregate {
        $children->each(function(string $name, array $config) use (&$entity) {
            $config = new Collection($config);
            $rel = new ValueObjectRelationship(
                new ClassName($config->get('class')),
                new RelationshipType($config->get('type')),
                $name,
                $config->get('child')['property'],
                $config->hasKey('collection') ?
                    (bool) $config->get('collection') : false
            );

            if ($config->hasKey('properties')) {
                $rel = $this->appendProperties(
                    $rel,
                    new Collection($config->get('properties'))
                );
            }

            $config = new Collection($config->get('child'));
            $child = new ValueObject(
                new ClassName($config->get('class')),
                $config->get('labels'),
                $rel
            );

            if ($config->hasKey('properties')) {
                $child = $this->appendProperties(
                    $child,
                    new Collection($config->get('properties'))
                );
            }

            $entity = $entity->withChild($child);
        });

        return $entity;
    }
}
