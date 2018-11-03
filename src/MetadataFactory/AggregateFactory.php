<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory,
    Metadata\Entity,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Factory,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Types,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
};

final class AggregateFactory implements MetadataFactory
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

        if ($config->contains('children')) {
            $children = $this->children(
                $this->map(
                    $config->get('children')
                )
            );
        }

        $entity = Aggregate::of(
            new ClassName($config->get('class')),
            new Identity(
                $config->get('identity')['property'],
                $config->get('identity')['type']
            ),
            Set::of('string', ...$config->get('labels')),
            $this->map($config['properties'] ?? [])->reduce(
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
            ),
            $children ?? Set::of(ValueObject::class)
        );

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
                    ($this->build)(
                        $config->get('type'),
                        $config
                    )
                );
            }
        );
    }

    private function children(MapInterface $children): SetInterface
    {
        return $children->reduce(
            Set::of(ValueObject::class),
            function(SetInterface $children, string $name, array $config): SetInterface {
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
                $child = ValueObject::of(
                    new ClassName($config->get('class')),
                    Set::of('string', ...$config->get('labels')),
                    $rel
                );

                if ($config->contains('properties')) {
                    $child = $this->appendProperties(
                        $child,
                        $this->map($config->get('properties'))
                    );
                }

                return $children->add($child);
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
