<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Immutable\CollectionInterface;

interface MetadataFactoryInterface
{
    public function make(CollectionInterface $config): EntityInterface;
}
