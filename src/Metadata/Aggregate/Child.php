<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata\Aggregate;

use Innmind\Neo4j\ONM\{
    Metadata\ClassName,
    Metadata\Property,
    Type,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class Child
{
    private ClassName $class;
    private Set $labels;
    private Child\Relationship $relationship;
    private Map $properties;

    public function __construct(
        ClassName $class,
        Set $labels,
        Child\Relationship $relationship,
        Set $properties
    ) {
        if ((string) $labels->type() !== 'string') {
            throw new \TypeError('Argument 2 must be of type Set<string>');
        }

        if ((string) $properties->type() !== Property::class) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type Set<%s>',
                Property::class
            ));
        }

        $this->class = $class;
        $this->labels = $labels;
        $this->relationship = $relationship;
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(Map $properties, Property $property): Map {
                return $properties->put($property->name(), $property);
            }
        );
    }

    public static function of(
        ClassName $class,
        Set $labels,
        Child\Relationship $relationship,
        Map $properties = null
    ): self {
        return new self(
            $class,
            $labels,
            $relationship,
            ($properties ?? Map::of('string', Type::class))->reduce(
                Set::of(Property::class),
                static function(Set $properties, string $property, Type $type): Set {
                    return $properties->add(new Property($property, $type));
                }
            )
        );
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    public function relationship(): Child\Relationship
    {
        return $this->relationship;
    }

    /**
     * @return Set<string>
     */
    public function labels(): Set
    {
        return $this->labels;
    }

    /**
     * @return Map<string, Property>
     */
    public function properties(): Map
    {
        return $this->properties;
    }
}
