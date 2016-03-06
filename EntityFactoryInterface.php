<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Immutable\CollectionInterface;

interface EntityFactoryInterface
{
    /**
     * Make a new instance for the entity whien the given identity
     *
     * @param IdentityInterface $identity
     * @param EntityInterface $meta
     * @param CollectionInterface $data
     *
     * @return object
     */
    public function make(
        IdentityInterface $identity,
        EntityInterface $meta,
        CollectionInterface $data
    );
}
