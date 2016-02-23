<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\Collection;
use Innmind\Immutable\TypedCollection;
use Innmind\Immutable\CollectionInterface;
use Innmind\Immutable\TypedCollectionInterface;

class ValueObject
{
    private $class;
    private $labels;
    private $relationship;
    private $properties;

    public function __construct(
        ClassName $class,
        array $labels,
        ValueObjectRelationship $relationship
    ) {
        $this->class = $class;
        $this->labels = new Collection($labels);
        $this->relationship = $relationship;
        $this->properties = new TypedCollection(Property::class, []);
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    public function relationship(): ValueObjectRelationship
    {
        return $this->relationship;
    }

    public function labels(): CollectionInterface
    {
        return $this->labels;
    }

    public function properties(): TypedCollectionInterface
    {
        return $this->properties;
    }

    /**
     * Add a property to the definition
     *
     * @param string $name
     * @param TypeInterface $type
     *
     * @return self
     */
    public function withProperty(string $name, TypeInterface $type): self
    {
        $valueObject = clone $this;
        $valueObject->properties = $this->properties->set(
            $name,
            new Property($name, $type)
        );

        return $valueObject;
    }
}
