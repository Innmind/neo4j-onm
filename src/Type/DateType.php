<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Exception\InvalidArgumentException,
};

final class DateType implements Type
{
    private $format;
    private $nullable = false;
    private $immutable = true;
    private static $identifiers;

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

    /**
     * {@inheritdoc}
     */
    public function forDatabase($value)
    {
        if ($this->nullable && $value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = new \DateTimeImmutable($value);
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new InvalidArgumentException(sprintf(
                'The value "%s" must be an instance of DateTimeInterface',
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
        if ($this->immutable) {
            return \DateTimeImmutable::createFromFormat($this->format, $value);
        }

        return \DateTime::createFromFormat($this->format, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
