<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadata\Entity,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Property,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Map;
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy\ReflectionStrategy,
};

final class AggregateExtractor implements DataExtractorInterface
{
    private ReflectionStrategy $extractionStrategy;

    public function __construct()
    {
        $this->extractionStrategy = new ReflectionStrategy;
    }

    public function __invoke(object $entity, Entity $meta): Map
    {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        /** @psalm-suppress MixedMethodCall */
        $data = $this
            ->extractProperties($entity, $meta->properties())
            ->put(
                $id = $meta->identity()->property(),
                $this
                    ->reflection($entity)
                    ->extract($id)
                    ->get($id)
                    ->value(),
            );

        /** @var Map<string, mixed> */
        return $meta
            ->children()
            ->reduce(
                $data,
                function(Map $carry, string $property, Child $child) use ($entity): Map {
                    return ($carry)(
                        $property,
                        $this->extractRelationship($child, $entity),
                    );
                },
            );
    }

    /**
     * @return Map<string, mixed>
     */
    private function extractRelationship(Child $child, object $entity): Map
    {
        /** @var object */
        $rel = $this
            ->reflection($entity)
            ->extract($property = $child->relationship()->property())
            ->get($property);

        /** @psalm-suppress MixedArgument */
        return $this
            ->extractProperties(
                $rel,
                $child->relationship()->properties(),
            )
            ->put(
                $property = $child->relationship()->childProperty(),
                $this->extractProperties(
                    $this
                        ->reflection($rel)
                        ->extract($property)
                        ->get($property),
                    $child->properties(),
                ),
            );
    }

    /**
     * @param Map<string, Property> $properties
     *
     * @return Map<string, mixed>
     */
    private function extractProperties(object $object, Map $properties): Map
    {
        $refl = $this->reflection($object);

        /** @var Map<string, mixed> */
        return $properties->toMapOf(
            'string',
            'mixed',
            static function(string $name, Property $property) use ($refl): \Generator {
                yield $name => $property
                    ->type()
                    ->forDatabase(
                        $refl->extract($name)->get($name)
                    );
            },
        );
    }

    private function reflection(object $object): ReflectionObject
    {
        return new ReflectionObject(
            $object,
            null,
            null,
            $this->extractionStrategy,
        );
    }
}
