<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

interface Identity
{
    /**
     * Return the raw value of the identifier
     *
     * @return mixed
     */
    public function value();

    /**
     * Return the string representation of the identifier
     */
    public function __toString(): string;
}
