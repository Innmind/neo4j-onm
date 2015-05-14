<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Metadata;

class MetadataRegistry
{
    protected $data = [];

    /**
     * Add a metadata object to the factory
     *
     * @param Metadata $meta
     *
     * @return MetadataRegistry self
     */
    public function addMetadata(Metadata $meta)
    {
        $this->data[$meta->getClass()] = $meta;

        return $this;
    }

    /**
     * Return a metadata object for the given class
     *
     * @param string $class
     *
     * @return Metadata
     */
    public function getMetadata($class)
    {
        return $this->data[(string) $class];
    }

    /**
     * Return all metadatas
     *
     * @return array
     */
    public function getMetadatas()
    {
        return $this->data;
    }
}
