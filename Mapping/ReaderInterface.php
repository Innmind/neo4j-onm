<?php

namespace Innmind\Neo4j\ONM\Mapping;

interface ReaderInterface
{
    /**
     * Load all the metadatas for the specified location
     *
     * @param string $location
     *
     * @return array
     */
    public function load($location);

    /**
     * Return the resources found at the specified location
     *
     * @param string $location
     *
     * @return array
     */
    public function getResources($location);
}
