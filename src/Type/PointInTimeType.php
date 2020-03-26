<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\InvalidArgumentException,
};
use Innmind\TimeContinuum\{
    Format,
    PointInTime as PointInTimeInterface,
    Earth\Format\ISO8601,
    Earth\PointInTime\PointInTime,
};

final class PointInTimeType implements Type
{
    private bool $nullable = false;
    private Format $format;

    public function __construct(Format $format = null)
    {
        $this->format = $format ?? new ISO8601;
    }

    public static function nullable(Format $format = null): self
    {
        $self = new self($format);
        $self->nullable = true;

        return $self;
    }

    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return;
        }

        if (!$value instanceof PointInTimeInterface) {
            /** @psalm-suppress MixedArgument */
            throw new InvalidArgumentException(\sprintf(
                'The value "%s" must be an instance of PointInTimeInterface',
                $value,
            ));
        }

        return $value->format($this->format);
    }

    public function fromDatabase($value)
    {
        /** @psalm-suppress MixedArgument */
        return new PointInTime($value);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
