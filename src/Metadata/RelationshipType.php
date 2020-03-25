<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\Str;

final class RelationshipType
{
    private string $type;

    public function __construct(string $type)
    {
        if (Str::of($type)->empty()) {
            throw new DomainException;
        }

        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
