<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\Type;

final class FloatType implements Type
{
    private bool $nullable = false;

    public static function nullable(): self
    {
        $self = new self;
        $self->nullable = true;

        return $self;
    }

    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return;
        }

        return (float) $value;
    }

    public function fromDatabase($value)
    {
        return (float) $value;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
