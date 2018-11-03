<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\Str;

final class RelationshipEdge extends Identity
{
    private $target;

    public function __construct(string $property, string $type, string $target)
    {
        parent::__construct($property, $type);

        if (Str::of($target)->empty()) {
            throw new DomainException;
        }

        $this->target = $target;
    }

    public function target(): string
    {
        return $this->target;
    }
}
