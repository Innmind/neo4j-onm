<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractorInterface,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\Property,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\CollectionInterface;
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy\ExtractionStrategiesInterface
};

class RelationshipExtractor implements DataExtractorInterface
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
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $refl = new ReflectionObject(
            $entity,
            null,
            null,
            $this->extractionStrategies
        );
        $data = $refl->extract([
            $id = $meta->identity()->property(),
            $start = $meta->startNode()->property(),
            $end = $meta->endNode()->property(),
        ]);
        $data = $data
            ->set(
                $id,
                $data->get($id)->value()
            )
            ->set(
                $start,
                $data->get($start)->value()
            )
            ->set(
                $end,
                $data->get($end)->value()
            );

        $meta
            ->properties()
            ->foreach(function(
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
                            $refl
                                ->extract([$name])
                                ->get($name)
                        )
                );
            });

        return $data;
    }
}
