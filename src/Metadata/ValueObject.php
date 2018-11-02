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

final class ValueObject
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
        $this->labels = Set::of('string', ...$labels);
        $this->relationship = $relationship;
        $this->properties = new Map('string', Property::class);
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    public function relationship(): ValueObjectRelationship
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
