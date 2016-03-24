<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractorInterface,
    Metadata\EntityInterface,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\Property,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection,
    MapInterface
};
use Innmind\Reflection\ReflectionObject;

class AggregateExtractor implements DataExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function extract($entity, EntityInterface $meta): CollectionInterface
    {
        if (!$meta instanceof Aggregate) {
            throw new InvalidArgumentException;
        }

        $data = $this->extractProperties($entity, $meta->properties());
        $data = $data->set(
            $id = $meta->identity()->property(),
            (new ReflectionObject($entity))
                ->extract([$id])
                ->get($id)
                ->value()
        );

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$data,
                $entity
            ) {
                $data = $data->set(
                    $property,
                    $this->extractRelationship(
                        $child,
                        $entity
                    )
                );
            });

        return $data;
    }

    private function extractRelationship(
        ValueObject $child,
        $entity
    ): CollectionInterface {
        $rel = (new ReflectionObject($entity))
            ->extract([
                $prop = $child->relationship()->property()
            ])
            ->get($prop);
        $data = $this
            ->extractProperties(
                $rel,
                $child->relationship()->properties()
            )
            ->set(
                $prop = $child->relationship()->childProperty(),
                $this->extractProperties(
                    (new ReflectionObject($rel))
                        ->extract([$prop])
                        ->get($prop),
                    $child->properties()
                )
            );

        return $data;
    }

    private function extractProperties(
        $object,
        MapInterface $properties
    ): CollectionInterface {
        $refl = new ReflectionObject($object);
        $data = new Collection([]);

        $properties->foreach(function(
            string $name,
            Property $property
        ) use (
            &$data,
            $refl
        ) {
            $data = $data->set(
                $name,
                $property
                    ->type()
                    ->forDatabase(
                        $refl->extract([$name])->get($name)
                    )
            );
        });

        return $data;
    }
}
