<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\Property,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy,
};

final class AggregateExtractor implements DataExtractorInterface
{
    private $extractionStrategy;

    public function __construct(ExtractionStrategy $extractionStrategy = null)
    {
        $this->extractionStrategy = $extractionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $entity, Entity $meta): MapInterface
    {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        $data = $this
            ->extractProperties($entity, $meta->properties())
            ->put(
                $id = $meta->identity()->property(),
                $this
                    ->reflection($entity)
                    ->extract($id)
                    ->get($id)
                    ->value()
            );

        return $meta
            ->children()
            ->reduce(
                $data,
                function(MapInterface $carry, string $property, ValueObject $child) use ($entity): MapInterface {
                    return $carry->put(
                        $property,
                        $this->extractRelationship(
                            $child,
                            $entity
                        )
                    );
                }
            );
    }

    /**
     * @return MapInterface<string, mixed>
     */
    private function extractRelationship(
        ValueObject $child,
        object $entity
    ): MapInterface {
        $rel = $this
            ->reflection($entity)
            ->extract($property = $child->relationship()->property())
            ->get($property);
        $data = $this
            ->extractProperties(
                $rel,
                $child->relationship()->properties()
            )
            ->put(
                $property = $child->relationship()->childProperty(),
                $this->extractProperties(
                    $this
                        ->reflection($rel)
                        ->extract($property)
                        ->get($property),
                    $child->properties()
                )
            );

        return $data;
    }

    /**
     * @param MapInterface<string, Property> $properties
     *
     * @return MapInterface<string, mixed>
     */
    private function extractProperties(
        object $object,
        MapInterface $properties
    ): MapInterface {
        $refl = $this->reflection($object);

        return $properties->reduce(
            new Map('string', 'mixed'),
            static function(MapInterface $carry, string $name, Property $property) use ($refl): MapInterface {
                return $carry->put(
                    $name,
                    $property
                        ->type()
                        ->forDatabase(
                            $refl->extract($name)->get($name)
                        )
                );
            }
        );
    }

    private function reflection(object $object): ReflectionObject
    {
        return new ReflectionObject(
            $object,
            null,
            null,
            $this->extractionStrategy
        );
    }
}
