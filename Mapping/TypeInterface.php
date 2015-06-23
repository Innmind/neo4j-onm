<?php

namespace Innmind\Neo4j\ONM\Mapping;

interface TypeInterface
{
    /**
     * Convert PHP value to database representation
     *
     * @param mixed $value
     * @param Property $property
     *
     * @return mixed
     */
    public function convertToDatabaseValue($value, Property $property);

    /**
     * Convert database value to PHP representation
     *
     * @param mixed $value
     * @param Property $property
     *
     * @return mixed
     */
    public function convertToPHPValue($value, Property $property);
}
