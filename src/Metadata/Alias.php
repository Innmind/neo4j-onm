<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;

/**
 * Entity alias
 */
final class Alias
{
    private $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
