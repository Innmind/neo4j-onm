<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
    Exception\InvalidArgumentException
};
use Innmind\TimeContinuum\{
    Format\ISO8601,
    PointInTimeInterface,
    PointInTime\Earth\PointInTime
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    SetInterface
};

final class PointInTimeType implements Type
{
    private $nullable = false;
    private $format;
    private $immutable = true;
    private static $identifiers;

    public function __construct()
    {
        $this->format = new ISO8601;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(MapInterface $config, Types $types): Type
    {
        $type = new self;

        if ($config->contains('nullable')) {
            $type->nullable = true;
        }

        if ($config->contains('format')) {
            $format = $config->get('format');
            $type->format = new $format;
        }

        return $type;
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
        if (self::$identifiers === null) {
            self::$identifiers = (new Set('string'))
                ->add('point_in_time');
        }

        return self::$identifiers;
    }
}
