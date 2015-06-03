<?php

namespace Innmind\Neo4j\ONM\Mapping;

class RelationshipMetadata extends Metadata
{
    protected $repository = 'Innmind\\Neo4j\\ONM\\RelationshipRepository';
    protected $type;
    protected $startNode;
    protected $endNode;

    /**
     * Set the relation type
     *
     * @param string $type
     *
     * @return RelationshipMetadata self
     */
    public function setType($type)
    {
        $this->type = strtoupper((string) $type);

        return $this;
    }

    /**
     * Return the relationship type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the property name used to store start node
     *
     * @param string $name
     *
     * @return RelationshipMetadata self
     */
    public function setStartNode($name)
    {
        $this->startNode = (string) $name;

        return $this;
    }

    /**
     * Check if a start node property is used
     *
     * @return bool
     */
    public function hasStartNode()
    {
        return $this->startNode !== null;
    }

    /**
     * Return the property name storing the start node
     *
     * @return string
     */
    public function getStartNode()
    {
        return $this->startNode;
    }

    /**
     * Set the property name used to store end node
     *
     * @param string $name
     *
     * @return RelationshipMetadata self
     */
    public function setEndNode($name)
    {
        $this->endNode = (string) $name;

        return $this;
    }

    /**
     * Check if a end node property is used
     *
     * @return bool
     */
    public function hasEndNode()
    {
        return $this->endNode !== null;
    }

    /**
     * Return the property name storing the end node
     *
     * @return string
     */
    public function getEndNode()
    {
        return $this->endNode;
    }
}
