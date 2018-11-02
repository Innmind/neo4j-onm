<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Immutable\MapInterface;

interface MetadataFactory
{
    /**
     * @param MapInterface<string, mixed> $config
     */
    public function __invoke(MapInterface $config): Entity;
}
