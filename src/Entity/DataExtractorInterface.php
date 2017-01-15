<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Immutable\CollectionInterface;

interface DataExtractorInterface
{
    /**
     * Extract the data for the given entity
     *
     * @param object $entity
     * @param EntityInterface $meta
     *
     * @return CollectionInterface
     */
    public function extract($entity, EntityInterface $meta): CollectionInterface;
}
