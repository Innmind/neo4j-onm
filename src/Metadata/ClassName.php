<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\Str;

final class ClassName
{
    /** @var class-string */
    private string $class;

    /**
     * @param class-string $class
     */
    public function __construct(string $class)
    {
        if (Str::of($class)->empty()) {
            throw new DomainException;
        }

        $this->class = $class;
    }

    /**
     * @return class-string
     */
    public function __toString(): string
    {
        return $this->class;
    }
}
