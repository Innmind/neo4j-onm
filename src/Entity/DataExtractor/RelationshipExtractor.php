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

    public function __invoke(object $entity, Entity $meta): Map
    {
        if (!$meta instanceof Relationship) {
            throw new InvalidArgumentException;
        }

        $refl = new ReflectionObject(
            $entity,
            null,
            null,
            $this->extractionStrategy,
        );
        /** @var Map<string, mixed> */
        $data = $refl->extract(
            $id = $meta->identity()->property(),
            $start = $meta->startNode()->property(),
            $end = $meta->endNode()->property(),
        );
        /** @psalm-suppress MixedMethodCall */
        $data = $data
            ($id, $data->get($id)->value())
            ($start, $data->get($start)->value())
            ($end, $data->get($end)->value());

        /** @var Map<string, mixed> */
        return $meta
            ->properties()
            ->reduce(
                $data,
                static function(Map $carry, string $name, Property $property) use ($refl): Map {
                    return ($carry)(
                        $name,
                        $property
                            ->type()
                            ->forDatabase(
                                $refl->extract($name)->get($name),
                            ),
                    );
                },
            );
    }
}
