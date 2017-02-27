<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\InvalidArgumentException;

final class RelationshipEdge extends Identity
{
    private $target;

    public function __construct(string $property, string $type, string $target)
    {
        parent::__construct($property, $type);

        if (empty($target)) {
            throw new InvalidArgumentException;
        }

        $this->target = $target;
    }

    public function target(): string
    {
        return $this->target;
    }
}
