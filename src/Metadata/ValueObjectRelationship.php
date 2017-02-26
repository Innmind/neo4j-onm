<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class ValueObjectRelationship
{
    private $class;
    private $type;
    private $property;
    private $childProperty;
    private $properties;

    public function __construct(
        ClassName $class,
        RelationshipType $type,
        string $property,
        string $childProperty
    ) {
        $this->class = $class;
        $this->type = $type;
        $this->property = $property;
        $this->childProperty = $childProperty;
        $this->properties = new Map('string', Property::class);
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    public function type(): RelationshipType
    {
        return $this->type;
    }

    public function property(): string
    {
        return $this->property;
    }

    /**
     * Return the property name where to find the child
     *
     * @return string
     */
    public function childProperty(): string
    {
        return $this->childProperty;
    }

    /**
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
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
        $valueObject->properties = $this->properties->put(
            $name,
            new Property($name, $type)
        );

        return $valueObject;
    }
}
