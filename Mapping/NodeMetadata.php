<?php

namespace Innmind\Neo4j\ONM\Mapping;

class NodeMetadata extends Metadata
{
    protected $labels = [];

    /**
     * Add a label associated to the entity
     *
     * @param string $label
     *
     * @return NodeMetadata self
     */
    public function addLabel($label)
    {
        $this->labels[] = ucfirst((string) $label);

        return $this;
    }

    /**
     * Return the entity labels
     *
     * @return array
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * {@inheritdoc}
     */
    public function isReference(Property $property)
    {
        return $property->getType() === 'relationship';
    }
}
