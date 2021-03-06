<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class Property
{
    private string $name;
    private Type $type;

    public function __construct(string $name, Type $type)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->type = $type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }
}
