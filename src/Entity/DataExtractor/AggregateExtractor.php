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
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy\ExtractionStrategiesInterface
};

class AggregateExtractor implements DataExtractorInterface
{
    private $extractionStrategies;

    public function __construct(ExtractionStrategiesInterface $extractionStrategies = null)
    {
        $this->extractionStrategies = $extractionStrategies;
    }

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
            $this
                ->reflection($entity)
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
        $rel = $this
            ->reflection($entity)
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
                    $this
                        ->reflection($rel)
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
        $refl = $this->reflection($object);
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

    private function reflection($object): ReflectionObject
    {
        return new ReflectionObject(
            $object,
            null,
            null,
            $this->extractionStrategies
        );
    }
}
