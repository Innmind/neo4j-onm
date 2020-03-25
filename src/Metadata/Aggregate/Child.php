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
    /** @var Set<string> */
    private Set $labels;
    private Child\Relationship $relationship;
    /** @var Map<string, Property> */
    private Map $properties;

    /**
     * @param Set<string> $labels
     * @param Set<Property> $properties
     */
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
        /** @var Map<string, Property> */
        $this->properties = $properties->reduce(
            Map::of('string', Property::class),
            static function(Map $properties, Property $property): Map {
                return $properties->put($property->name(), $property);
            }
        );
    }

    /**
     * @param Set<string> $labels
     * @param Map<string, Type>|null $properties
     */
    public static function of(
        ClassName $class,
        Set $labels,
        Child\Relationship $relationship,
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
            $labels,
            $relationship,
            $properties,
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
