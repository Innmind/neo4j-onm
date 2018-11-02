<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Immutable\{
    MapInterface,
    SetInterface,
};

interface Type
{
    /**
     * Build a type instance out of a config array
     *
     * @param MapInterface<string, mixed> $config
     */
    public static function fromConfig(MapInterface $config, Types $types): self;

    /**
     * Format the given value as a valid database value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function forDatabase($value);

    /**
     * Format the given value as a valid PHP value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function fromDatabase($value);

    /**
     * Check if the property value can be null
     */
    public function isNullable(): bool;

    /**
     * Return the identifiers that can be used to reference the type class
     *
     * @return SetInterface<string>
     */
    public static function identifiers(): SetInterface;
}
