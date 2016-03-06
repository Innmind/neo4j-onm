<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactoryInterface,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\RelationshipType,
    Repository as EntityRepository,
    EntityFactory,
    Types
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class RelationshipFactory implements MetadataFactoryInterface
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
        $entity = new Relationship(
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
            new RelationshipType($config->get('rel_type')),
            new Identity(
                $config->get('startNode')['property'],
                $config->get('startNode')['type']
            ),
            new Identity(
                $config->get('endNode')['property'],
                $config->get('endNode')['type']
            )
        );

        if ($config->hasKey('properties')) {
            $entity = $this->appendProperties(
                $entity,
                new Collection($config->get('properties'))
            );
        }

        return $entity;
    }

    private function appendProperties(
        Relationship $relationship,
        CollectionInterface $properties
    ): Relationship {
        $properties->each(function(string $name, array $config) use (&$relationship) {
            $relationship = $relationship->withProperty(
                $name,
                $this->types->build(
                    $config['type'],
                    new Collection($config)
                )
            );
        });

        return $relationship;
    }
}
