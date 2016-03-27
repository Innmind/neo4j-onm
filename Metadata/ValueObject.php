<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\{
    Set,
    Map,
    SetInterface,
    MapInterface
};

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
        $this->labels = new Set('string');
        $this->relationship = $relationship;
        $this->properties = new Map('string', Property::class);

        foreach ($labels as $label) {
            $this->labels = $this->labels->add($label);
        }
    }

    public function class(): ClassName
    {
        return $this->class;
    }

    public function relationship(): ValueObjectRelationship
    {
        return $this->relationship;
    }

    public function labels(): SetInterface
    {
        return $this->labels;
    }

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
