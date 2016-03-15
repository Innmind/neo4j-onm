<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Metadatas,
    Metadata\Aggregate,
    Metadata\Relationship,
    Entity\DataExtractor\AggregateExtractor,
    Entity\DataExtractor\RelationshipExtractor,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    CollectionInterface
};

class DataExtractor
{
    private $metadatas;

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
     * @return CollectionInterface
     */
    public function extract($entity): CollectionInterface
    {
        $meta = $this->metadatas->get(get_class($entity));

        return $this
            ->extractors
            ->get(get_class($meta))
            ->extract($entity, $meta);
    }
}
