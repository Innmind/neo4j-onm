<?php

namespace Innmind\Neo4j\ONM\Mapping;

class RelationshipMetadata extends Metadata
{
    protected $repository = 'Innmind\\Neo4j\\ONM\\RelationshipRepository';
    protected $type;

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
}
