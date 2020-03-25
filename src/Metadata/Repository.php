<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Repository as RepositoryInterface,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

/**
 * Holds the repository class for an entity
 */
final class Repository
{
    /** @var class-string<RepositoryInterface> */
    private string $class;

    /**
     * @param class-string<RepositoryInterface> $class
     */
    public function __construct(string $class)
    {
        if (Str::of($class)->empty()) {
            throw new DomainException;
        }

        $this->class = $class;
    }

    /**
     * @return class-string<RepositoryInterface>
     */
    public function __toString(): string
    {
        return $this->class;
    }
}
