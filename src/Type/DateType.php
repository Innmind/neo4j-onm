<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class DateType implements Type
{
    private $nullable = false;
    private $format = \DateTime::ISO8601;
    private $immutable = true;
    private static $identifiers;

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
            $type->format = $config->get('format');
        }

        if ($config->contains('immutable')) {
            $type->immutable = (bool) $config->get('immutable');
        }

        return $type;
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

    /**
     * {@inheritdoc}
     */
    public static function identifiers(): SetInterface
    {
        return self::$identifiers ?? self::$identifiers = Set::of('string', 'date', 'datetime');
    }
}
