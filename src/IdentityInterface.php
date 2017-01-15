<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

interface IdentityInterface
{
    /**
     * Return the raw value of the identifier
     *
     * @return mixed
     */
    public function value();

    /**
     * Return the string representation of the identifier
     *
     * @return string
     */
    public function __toString(): string;
}
