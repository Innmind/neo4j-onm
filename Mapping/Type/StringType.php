<?php

namespace Innmind\Neo4j\ONM\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\TypeInterface;
use Innmind\Neo4j\ONM\Mapping\Property;

class StringType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, Property $property)
    {
        if ($property->isNullable() && $value === null) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Property $property)
    {
        return (string) $value;
    }
}
