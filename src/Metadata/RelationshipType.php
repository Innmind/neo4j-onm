<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Exception\DomainException;

final class RelationshipType
{
    private $type;

    public function __construct(string $type)
    {
        if (empty($type)) {
            throw new DomainException;
        }

        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
