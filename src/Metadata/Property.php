<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\TypeInterface;

class Property
{
    private $name;
    private $type;

    public function __construct(string $name, TypeInterface $type)
    {
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

    public function type(): TypeInterface
    {
        return $this->type;
    }
}
