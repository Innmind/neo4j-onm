<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata\Aggregate\Child;

use Innmind\Neo4j\ONM\{
    Metadata\ClassName,
    Metadata\RelationshipType,
    Metadata\Property,
    Type,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};

final class Relationship
{
    private ClassName $class;
    private RelationshipType $type;
    private string $property;
    private string $childProperty;
    /** @var Map<string, Property> */
    private Map $properties;

    /**
     * @param Set<Property> $properties
     */
    public function __construct(
        ClassName $class,
        RelationshipType $type,
        string $property,
        string $childProperty,
        Set $properties
    ) {
        if (Str::of($property)->empty() || Str::of($childProperty)->empty()) {
            throw new DomainException;
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 5 must be of type Set<%s>',
                Property::class
            ));
        }

        $this->class = $class;
        $this->type = $type;
        $this->property = $property;
        $this->childProperty = $childProperty;
        /** @var Map<string, Property> */
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(Map $properties, Property $property): Map {
                return $properties->put($property->name(), $property);
            }
        );
    }

    /**
     * @param Map<string, Type>|null $properties
     */
    public static function of(
        ClassName $class,
        RelationshipType $type,
        string $property,
        string $childProperty,
        Map $properties = null
    ): self {
        /** @var Map<string, Type> */
        $properties ??= Map::of('string', Type::class);
        /** @var Set<Property> */
        $properties = $properties->toSetOf(
            Property::class,
            static fn(string $property, Type $type): \Generator => yield new Property($property, $type),
        );

        return new self(
            $class,
            $type,
            $property,
            $childProperty,
            $properties,
        );
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
     * @return Map<string, Property>
     */
    public function properties(): Map
    {
        return $this->properties;
    }
}
