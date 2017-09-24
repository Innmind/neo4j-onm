<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\DomainException
};

final class Property
{
    private $name;
    private $type;

    public function __construct(string $name, Type $type)
    {
        if (empty($name)) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->type = $type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function type(): Type
    {
        return $this->type;
    }
}
