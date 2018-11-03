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
    SetInterface,
    Set,
    Str,
};

final class ChildRelationship
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
        string $childProperty,
        SetInterface $properties
    ) {
        if (Str::of($property)->empty() || Str::of($childProperty)->empty()) {
            throw new DomainException;
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 5 must be of type SetInterface<%s>',
                Property::class
            ));
        }

        $this->class = $class;
        $this->type = $type;
        $this->property = $property;
        $this->childProperty = $childProperty;
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(MapInterface $properties, Property $property): MapInterface {
                return $properties->put($property->name(), $property);
            }
        );
    }

    public static function of(
        ClassName $class,
        RelationshipType $type,
        string $property,
        string $childProperty,
        MapInterface $properties = null
    ): self {
        return new self(
            $class,
            $type,
            $property,
            $childProperty,
            ($properties ?? Map::of('string', Type::class))->reduce(
                Set::of(Property::class),
                static function(SetInterface $properties, string $property, Type $type): SetInterface {
                    return $properties->add(new Property($property, $type));
                }
            )
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
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }
}
