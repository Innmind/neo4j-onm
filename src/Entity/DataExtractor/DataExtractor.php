<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor as DataExtractorInterface,
    Metadatas,
    Metadata\Aggregate,
    Metadata\Relationship,
};
use Innmind\Immutable\Map;

final class DataExtractor
{
    private Metadatas $metadata;
    /** @var Map<string, DataExtractorInterface> */
    private Map $extractors;

    /**
     * @param Map<string, DataExtractorInterface>|null $extractors
     */
    public function __construct(
        Metadatas $metadata,
        Map $extractors = null
    ) {
        $this->metadata = $metadata;
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<string, DataExtractorInterface>
         */
        $this->extractors = $extractors ?? Map::of('string', DataExtractorInterface::class)
            (Aggregate::class, new AggregateExtractor)
            (Relationship::class, new RelationshipExtractor);

        if (
            (string) $this->extractors->keyType() !== 'string' ||
            (string) $this->extractors->valueType() !== DataExtractorInterface::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type Map<string, %s>',
                DataExtractorInterface::class
            ));
        }
    }

    /**
     * Extract raw data from entity based on the defined mapping
     *
     * @return Map<string, mixed>
     */
    public function __invoke(object $entity): Map
    {
        $meta = ($this->metadata)(get_class($entity));
        $extract = $this->extractors->get(get_class($meta));

        return $extract($entity, $meta);
    }
}
