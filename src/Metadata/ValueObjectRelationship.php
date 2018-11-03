<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
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
        if (Str::of($property)->empty() || Str::of($childProperty)->empty()) {
            throw new DomainException;
        }

        $this->class = $class;
        $this->type = $type;
        $this->property = $property;
        $this->childProperty = $childProperty;
        $this->properties = new Map('string', Property::class);
    }

    public static function of(
        ClassName $class,
        RelationshipType $type,
        string $property,
        string $childProperty
    ): self {
        return new self($class, $type, $property, $childProperty);
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

    public function withProperty(string $name, Type $type): self
    {
        $valueObject = clone $this;
        $valueObject->properties = $this->properties->put(
            $name,
            new Property($name, $type)
        );

        return $valueObject;
    }
}
