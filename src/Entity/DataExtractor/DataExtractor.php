<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadatas,
    Metadata\Aggregate,
    Metadata\Relationship,
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
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                DataExtractorInterface::class
            ));
        }
    }

    /**
     * Extract raw data from entity based on the defined mapping
     *
     * @return MapInterface<string, mixed>
     */
    public function extract(object $entity): MapInterface
    {
        $meta = $this->metadatas->get(get_class($entity));

        return $this
            ->extractors
            ->get(get_class($meta))
            ->extract($entity, $meta);
    }
}
