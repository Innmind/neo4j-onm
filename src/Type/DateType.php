<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\InvalidArgumentException,
};

final class DateType implements Type
{
    private string $format;
    private bool $nullable = false;
    private bool $immutable = true;

    public function __construct(string $format = null)
    {
        $this->format = $format ?? \DateTime::ISO8601;
    }

    public static function nullable(string $format = null): self
    {
        $self = new self($format);
        $self->nullable = true;

        return $self;
    }

    public static function mutable(string $format = null): self
    {
        $self = new self($format);
        $self->immutable = false;

        return $self;
    }

    public static function nullableMutable(string $format = null): self
    {
        $self = new self($format);
        $self->nullable = true;
        $self->immutable = false;

        return $self;
    }

    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return;
        }

        if (\is_string($value)) {
            $value = new \DateTimeImmutable($value);
        }

        if (!$value instanceof \DateTimeInterface) {
            /** @psalm-suppress MixedArgument */
            throw new InvalidArgumentException(\sprintf(
                'The value "%s" must be an instance of DateTimeInterface',
                $value,
            ));
        }

        return $value->format($this->format);
    }

    public function fromDatabase($value)
    {
        if ($this->immutable) {
            /** @psalm-suppress MixedArgument */
            return \DateTimeImmutable::createFromFormat($this->format, $value);
        }

        /** @psalm-suppress MixedArgument */
        return \DateTime::createFromFormat($this->format, $value);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
