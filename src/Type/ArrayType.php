<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\RecursiveTypeDeclaration,
};

final class ArrayType implements Type
{
    private $nullable = false;
    private $inner;

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

    /**
     * {@inheritdoc}
     */
    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return null;
        }

        $array = [];

        foreach ($value as $sub) {
            $array[] = $this->inner->forDatabase($sub);
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        $array = [];

        foreach ($value as $sub) {
            $array[] = $this->inner->fromDatabase($sub);
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
