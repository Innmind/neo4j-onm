<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\RecursiveTypeDeclaration,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Set;

final class SetType implements Type
{
    private bool $nullable = false;
    private Type $inner;
    private string $type;

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
            !$value instanceof Set ||
            (string) $value->type() !== $this->type
        ) {
            throw new InvalidArgumentException(sprintf(
                'The set must be an instance of Set<%s>',
                $this->type
            ));
        }

        return $value->reduce(
            [],
            function(array $carry, $value): array {
                /** @psalm-suppress MixedAssignment */
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
        $set = Set::of($this->type);

        /** @var mixed $sub */
        foreach ($value as $sub) {
            /** @psalm-suppress MixedArgument */
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
