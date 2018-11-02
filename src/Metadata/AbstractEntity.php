<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Type;
use Innmind\Immutable\{
    MapInterface,
    Map,
};

abstract class AbstractEntity
{
    private $class;
    private $identity;
    private $repository;
    private $factory;
    private $properties;

    public function __construct(
        ClassName $class,
        Identity $id,
        Repository $repository,
        Factory $factory
    ) {
        $this->class = $class;
        $this->identity = $id;
        $this->repository = $repository;
        $this->factory = $factory;
        $this->properties = new Map('string', Property::class);
    }

    /**
     * {@inheritdoc}
     */
    public function identity(): Identity
    {
        return $this->identity;
    }

    /**
     * {@inheritdoc}
     */
    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): Factory
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function class(): ClassName
    {
        return $this->class;
    }

    public function withProperty(string $name, Type $type): Entity
    {
        $entity = clone $this;
        $entity->properties = $this->properties->put(
            $name,
            new Property($name, $type)
        );

        return $entity;
    }
}
