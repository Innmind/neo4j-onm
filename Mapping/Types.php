<?php

namespace Innmind\Neo4j\ONM\Mapping;

use Innmind\Neo4j\ONM\Exception\InvalidTypeException;

class Types
{
    protected static $map = [
        'array' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\ArrayType',
        'bool' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\BooleanType',
        'boolean' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\BooleanType',
        'float' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\FloatType',
        'int' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\IntType',
        'integer' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\IntType',
        'json' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\JsonType',
        'string' => 'Innmind\\Neo4j\\ONM\\Mapping\\Type\\StringType',
    ];
    protected static $interface = 'Innmind\\Neo4j\\ONM\\Mapping\\TypeInterface';

    /**
     * Add a new data type
     *
     * @param string $name
     * @param string $class
     *
     * @throws InvalidTypeException If the class doesn't implement interface
     *
     * @return void
     */
    public static function addType($name, $class)
    {
        $refl = new \ReflectionClass($class);

        if (!$refl->implementsInterface(self::$interface)) {
            throw new InvalidTypeException(sprintf(
                'The class "%s" must implement interface "%s"',
                $class,
                self::$interface
            ));
        }

        self::$map[(string) $name] = (string) $class;
    }

    /**
     * Return a new instancce for the wished type
     *
     * @param string $name
     *
     * @throws InvalidTypeException If the type doesn't exist
     *
     * @return TypeInterface
     */
    public function getType($name)
    {
        if (!isset(self::$map[(string) $name])) {
            throw new InvalidTypeException(sprintf(
                'Unknown type "%s"',
                $name
            ));
        }

        return new self::$map[(string) $name];
    }
}
