<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadatas,
    Metadata\Aggregate,
    Metadata\Relationship,
    Entity\DataExtractor\AggregateExtractor,
    Entity\DataExtractor\RelationshipExtractor,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class DataExtractor
{
    private $metadatas;
    private $extractors;

    public function __construct(
        Metadatas $metadatas,
        MapInterface $extractors = null
    ) {
        $this->metadatas = $metadatas;
        $this->extractors = $extractors ?? (new Map('string', DataExtractorInterface::class))
            ->put(Aggregate::class, new AggregateExtractor)
            ->put(Relationship::class, new RelationshipExtractor);

        if (
            (string) $this->extractors->keyType() !== 'string' ||
            (string) $this->extractors->valueType() !== DataExtractorInterface::class
        ) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * Extract raw data from entity based on the defined mapping
     *
     * @param object $entity
     *
     * @return MapInterface<string, mixed>
     */
    public function extract($entity): MapInterface
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException;
        }

        $meta = $this->metadatas->get(get_class($entity));

        return $this
            ->extractors
            ->get(get_class($meta))
            ->extract($entity, $meta);
    }
}
