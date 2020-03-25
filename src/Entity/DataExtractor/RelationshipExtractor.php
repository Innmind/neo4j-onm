<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Map;
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy\ReflectionStrategy,
};

final class RelationshipExtractor implements DataExtractorInterface
{
    private ReflectionStrategy $extractionStrategy;

    public function __construct()
    {
        $this->extractionStrategy = new ReflectionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $entity, Entity $meta): Map
    {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $refl = new ReflectionObject(
            $entity,
            null,
            null,
            $this->extractionStrategy
        );
        $data = $refl->extract(
            $id = $meta->identity()->property(),
            $start = $meta->startNode()->property(),
            $end = $meta->endNode()->property()
        );
        $data = $data
            ->put(
                $id,
                $data->get($id)->value()
            )
            ->put(
                $start,
                $data->get($start)->value()
            )
            ->put(
                $end,
                $data->get($end)->value()
            );

        return $meta
            ->properties()
            ->reduce(
                $data,
                static function(Map $carry, string $name, Property $property) use ($refl): Map {
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
}
