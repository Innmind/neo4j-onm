<?php

namespace Innmind\Neo4j\ONM\Generator;

use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\ONM\GeneratorInterface;

class IdGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function generate(UnitOfWork $uow, $entity)
    {
        trigger_error('The ID generator is here for sample purposes, DO NOT USE it in production');

        return uniqid(get_class($entity), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy()
    {
        return 'AUTO';
    }
}
