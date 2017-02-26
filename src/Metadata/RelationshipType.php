<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

final class RelationshipType
{
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
