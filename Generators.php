<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Generator\UUIDGenerator;
use Innmind\Neo4j\ONM\Generator\IdGenerator;

class Generators
{
    protected static $map;

    /**
     * Add a new generator
     *
     * @param GeneratorInterface $generator
     *
     * @return void
     */
    public static function addGenerator(GeneratorInterface $generator)
    {
        if (self::$map === null) {
            self::addDefaults();
        }

        self::$map[$generator->getStrategy()] = $generator;
    }

    /**
     * Return generator for the given strategy wished
     *
     * @param string $strategy
     *
     * @throws InvalidArgumentException If the strategy doesn't exist
     *
     * @return GeneratorInterface
     */
    public static function getGenerator($strategy)
    {
        if (self::$map === null) {
            self::addDefaults();
        }

        if (!isset(self::$map[(string) $strategy])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown "%s" strategy',
                $strategy
            ));
        }

        return self::$map[(string) $strategy];
    }

    /**
     * Return the list of supported strategies
     *
     * @return array
     */
    public static function getStrategies()
    {
        if (self::$map === null) {
            self::addDefaults();
        }

        return array_keys(self::$map);
    }

    /**
     * Add defaults
     *
     * @return void
     */
    protected static function addDefaults()
    {
        $uuid = new UUIDGenerator;
        $id = new IdGenerator;

        self::$map = [
            $uuid->getStrategy() => $uuid,
            $id->getStrategy() => $id,
        ];
    }
}
