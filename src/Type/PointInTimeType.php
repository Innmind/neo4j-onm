<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
    Exception\InvalidArgumentException,
};
use Innmind\TimeContinuum\{
    FormatInterface,
    Format\ISO8601,
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class PointInTimeType implements Type
{
    private $nullable = false;
    private $format;
    private static $identifiers;

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
    public static function fromConfig(MapInterface $config, Types $build): Type
    {
        if ($config->contains('format')) {
            $format = $config->get('format');
            $format = new $format;
        }

        if ($config->contains('nullable')) {
            return self::nullable($format ?? null);
        }

        return new self($format ?? null);
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

    /**
     * {@inheritdoc}
     */
    public static function identifiers(): SetInterface
    {
        return self::$identifiers ?? self::$identifiers = Set::of('string', 'point_in_time');
    }
}
