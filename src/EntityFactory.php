<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Immutable\MapInterface;

interface EntityFactory
{
    /**
     * Make a new instance for the entity whien the given identity
     *
     * @param MapInterface<string, mixed> $data
     */
    public function __invoke(
        Identity $identity,
        Entity $meta,
        MapInterface $data
    ): object;
}
