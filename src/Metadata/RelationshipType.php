<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\InvalidArgumentException;

final class RelationshipType
{
    private $type;

    public function __construct(string $type)
    {
        if (empty($type)) {
            throw new InvalidArgumentException;
        }

        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
