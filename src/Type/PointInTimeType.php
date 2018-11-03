<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\InvalidArgumentException,
};
use Innmind\TimeContinuum\{
    FormatInterface,
    Format\ISO8601,
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
};

final class PointInTimeType implements Type
{
    private $nullable = false;
    private $format;

    public function __construct(FormatInterface $format = null)
    {
        $this->format = $format ?? new ISO8601;
    }

    public static function nullable(FormatInterface $format = null): self
    {
        $self = new self($format);
        $self->nullable = true;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return;
        }

        if (!$value instanceof PointInTimeInterface) {
            throw new InvalidArgumentException(sprintf(
                'The value "%s" must be an instance of PointInTimeInterface',
                $value
            ));
        }

        return $value->format($this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        return new PointInTime($value);
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
