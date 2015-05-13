<?php

namespace Innmind\Neo4j\ONM\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\TypeInterface;
use Innmind\Neo4j\ONM\Mapping\Property;

class DateType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, Property $property)
    {
        if (is_string($value)) {
            $value = new \DateTime($value);
        }

        if (!is_object($value) && !($value instanceof \DateTime)) {
            throw new \InvalidArgumentException(sprintf(
                'Property "%s" must be a string or a DateTime object',
                $property->getName()
            ));
        }

        return $value->format(\DateTime::ISO8601);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Property $property)
    {
        return new \DateTime($value);
    }
}
