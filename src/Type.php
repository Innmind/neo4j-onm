<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

interface Type
{
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
}
