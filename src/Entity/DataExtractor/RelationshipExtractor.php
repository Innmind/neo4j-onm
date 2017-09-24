<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadata\Entity,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\MapInterface;
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategyInterface
};

final class RelationshipExtractor implements DataExtractorInterface
{
    private $extractionStrategy;

    public function __construct(ExtractionStrategyInterface $extractionStrategy = null)
    {
        $this->extractionStrategy = $extractionStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($entity, Entity $meta): MapInterface
    {
        if (!is_object($entity) || !$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $refl = new ReflectionObject(
            $entity,
            null,
            null,
            $this->extractionStrategy
        );
        $data = $refl->extract([
            $id = $meta->identity()->property(),
            $start = $meta->startNode()->property(),
            $end = $meta->endNode()->property(),
        ]);
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
                function(MapInterface $carry, string $name, Property $property) use ($refl): MapInterface {
                    return $carry->put(
                        $name,
                        $property
                            ->type()
                            ->forDatabase(
                                $refl->extract([$name])->get($name)
                            )
                    );
                }
            );
    }
}
