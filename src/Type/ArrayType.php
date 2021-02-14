<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\RecursiveTypeDeclaration,
};

final class ArrayType implements Type
{
    private bool $nullable = false;
    private Type $inner;

    public function __construct(Type $inner)
    {
        if ($inner instanceof self) {
            throw new RecursiveTypeDeclaration;
        }

        $this->inner = $inner;
    }

    public static function nullable(Type $inner): self
    {
        $self = new self($inner);
        $self->nullable = true;

        return $self;
    }

    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return;
        }

        $array = [];

        /** @var mixed $sub */
        foreach ($value as $sub) {
            /** @psalm-suppress MixedAssignment */
            $array[] = $this->inner->forDatabase($sub);
        }

        return $array;
    }

    public function fromDatabase($value)
    {
        $array = [];

        /** @var mixed $sub */
        foreach ($value as $sub) {
            /** @psalm-suppress MixedAssignment */
            $array[] = $this->inner->fromDatabase($sub);
        }

        return $array;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
