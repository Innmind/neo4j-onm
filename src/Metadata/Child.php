<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Type;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
};

final class Child
{
    private $class;
    private $labels;
    private $relationship;
    private $properties;

    public function __construct(
        ClassName $class,
        SetInterface $labels,
        ChildRelationship $relationship,
        SetInterface $properties
    ) {
        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 2 must be of type SetInterface<string>');
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type SetInterface<%s>',
                Property::class
            ));
        }

        $this->class = $class;
        $this->labels = $labels;
        $this->relationship = $relationship;
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(MapInterface $properties, Property $property): MapInterface {
                return $properties->put($property->name(), $property);
            }
        );
    }

    public static function of(
        ClassName $class,
        SetInterface $labels,
        ChildRelationship $relationship,
        MapInterface $properties = null
    ): self {
        return new self(
            $class,
            $labels,
            $relationship,
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

    public function relationship(): ChildRelationship
    {
        return $this->relationship;
    }

    /**
     * @return SetInterface<string>
     */
    public function labels(): SetInterface
    {
        return $this->labels;
    }

    /**
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }
}
