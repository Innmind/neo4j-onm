<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

class RelationshipEdge extends Identity
{
    private $target;

    public function __construct(string $property, string $type, string $target)
    {
        parent::__construct($property, $type);

        $this->target = $target;
    }

    public function target(): string
    {
        return $this->target;
    }
}
