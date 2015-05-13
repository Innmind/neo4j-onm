<?php

namespace Innmind\Neo4j\ONM\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\TypeInterface;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Types;
use Innmind\Neo4j\ONM\Exception\IncompletePropertyDefinitionException;

class ArrayType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, Property $property)
    {
        return $this->convert($value, $property, 'convertToDatabaseValue');
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Property $property)
    {
        return $this->convert($value, $property, 'convertToPHPValue');
    }

    /**
     * Use inner type to convert each array's value
     *
     * @param mixed $value
     * @param Property $property
     * @param string $method
     *
     * @return array
     */
    protected function convert($value, Property $property, $method)
    {
        if (!$property->hasOption('inner_type')) {
            throw new IncompletePropertyDefinitionException(sprintf(
                'An inner type must be defined for the property "%s"',
                $property->getName()
            ));
        }

        $value = (array) $value;
        $type = Types::getType($property->getOption('inner_type'));

        if ($type instanceof self) {
            throw new \LogicException('Array imbrication is not allowed');
        }

        foreach ($value as &$val) {
            $val = $type->{$method}($val, $property);
        }

        return $value;
    }
}
