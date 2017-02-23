<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Exception\InvalidArgumentException
};
use Innmind\TimeContinuum\{
    Format\ISO8601,
    PointInTimeInterface,
    PointInTime\Earth\PointInTime
};
use Innmind\Immutable\{
    CollectionInterface,
    Set,
    SetInterface
};

class PointInTimeType implements TypeInterface
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
    public static function fromConfig(CollectionInterface $config): TypeInterface
    {
        $type = new self;

        if ($config->hasKey('nullable')) {
            $type->nullable = true;
        }

        if ($config->hasKey('format')) {
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