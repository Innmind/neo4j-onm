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
    Map,
};

final class DataExtractor
{
    private Metadatas $metadata;
    private Map $extractors;

    public function __construct(
        Metadatas $metadata,
        MapInterface $extractors = null
    ) {
        $this->metadata = $metadata;
        $this->extractors = $extractors ?? Map::of('string', DataExtractorInterface::class)
            (Aggregate::class, new AggregateExtractor)
            (Relationship::class, new RelationshipExtractor);

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
    public function __invoke(object $entity): MapInterface
    {
        $meta = ($this->metadata)(get_class($entity));
        $extract = $this->extractors->get(get_class($meta));

        return $extract($entity, $meta);
    }
}
