<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Immutable\MapInterface;

interface DataExtractor
{
    /**
     * Extract the data for the given entity
     *
     * @return MapInterface<string, mixed>
     */
    public function __invoke(object $entity, Entity $meta): MapInterface;
}
