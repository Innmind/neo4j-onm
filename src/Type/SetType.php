<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\RecursiveTypeDeclaration,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class SetType implements Type
{
    private $nullable = false;
    private $inner;
    private $type;
    private static $identifiers;

    public function __construct(Type $inner, string $type)
    {
        if ($inner instanceof self) {
            throw new RecursiveTypeDeclaration;
        }

        $this->inner = $inner;
        $this->type = $type;
    }

    public static function nullable(Type $inner, string $type): self
    {
        $self = new self($inner, $type);
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

        if (
            !$value instanceof SetInterface ||
            (string) $value->type() !== $this->type
        ) {
            throw new InvalidArgumentException(sprintf(
                'The set must be an instance of SetInterface<%s>',
                $this->type
            ));
        }

        return $value->reduce(
            [],
            function(array $carry, $value): array {
                $carry[] = $this->inner->forDatabase($value);

                return $carry;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        $set = new Set($this->type);

        foreach ($value as $sub) {
            $set = $set->add($this->inner->fromDatabase($sub));
        }

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
