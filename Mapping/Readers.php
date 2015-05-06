<?php

namespace Innmind\Neo4j\ONM\Mapping;

use Innmind\Neo4j\ONM\Mapping\Reader\YamlReader;
use Innmind\Neo4j\ONM\Exception\InvalidReaderTypeException;

class Readers
{
    protected static $map = [];

    /**
     * Associate a type to a reader
     *
     * @param string $type
     * @param ReaderInterface $reader
     *
     * @return void
     */
    public static function addReader($type, ReaderInterface $reader)
    {
        if (!isset(self::$map[(string) $type])) {
            self::$map[(string) $type] = $reader;
        }
    }

    /**
     * Return an reader instance for the wished type
     *
     * @param string $type
     *
     * @return ReaderInterface
     */
    public static function getReader($type)
    {
        if (count(self::$map) === 0) {
            self::addDefaults();
        }

        if (!isset(self::$map[(string) $type])) {
            throw new InvalidReaderTypeException(sprintf(
                'Unknown reader for the type "%s"',
                $type
            ));
        }

        return self::$map[(string) $type];
    }

    /**
     * Init default readers
     *
     * @return void
     */
    protected function addDefaults()
    {
        self::$map = [
            'yaml' => new YamlReader
        ];
    }
}
