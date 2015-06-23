<?php

namespace Innmind\Neo4j\ONM\Generator;

use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\ONM\GeneratorInterface;
use Rhumsaa\Uuid\Uuid;

class UUIDGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(UnitOfWork $uow, $entity)
    {
        return (string) Uuid::uuid4();
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy()
    {
        return 'UUID';
    }
}
