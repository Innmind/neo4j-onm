<?php

namespace Innmind\Neo4j\ONM\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\TypeInterface;
use Innmind\Neo4j\ONM\Mapping\Property;

class JsonType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, Property $property)
    {
        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Property $property)
    {
        return json_decode(
            $value,
            $property->hasOption('associative') ?
                (bool) $property->getOption('associative'):
                false
        );
    }
}
