<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Immutable\MapInterface;

interface MetadataFactoryInterface
{
    /**
     * @param  MapInterface<string, mixed> $config
     */
    public function make(MapInterface $config): EntityInterface;
}
