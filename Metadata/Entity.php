<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\{
    Map,
    MapInterface
};

abstract class Entity
{
    private $class;
    private $identity;
    private $repository;
    private $factory;
    private $alias;
    private $properties;

    public function __construct(
        ClassName $class,
        Identity $id,
        Repository $repository,
        Factory $factory,
        Alias $alias
    ) {
        $this->class = $class;
        $this->identity = $id;
        $this->repository = $repository;
        $this->factory = $factory;
        $this->alias = $alias;
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
    public function alias(): Alias
    {
        return $this->alias;
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

    /**
     * Add a property to the definition
     *
     * @param string $name
     * @param TypeInterface $type
     *
     * @return EntityInterface
     */
    public function withProperty(string $name, TypeInterface $type): EntityInterface
    {
        $entity = clone $this;
        $entity->properties = $this->properties->put(
            $name,
            new Property($name, $type)
        );

        return $entity;
    }
}
