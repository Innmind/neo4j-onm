<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Types,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    SetInterface
};

class DateType implements TypeInterface
{
    private $nullable = false;
    private $format = \DateTime::ISO8601;
    private $immutable = true;
    private static $identifiers;

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(MapInterface $config, Types $types): TypeInterface
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
        if (self::$identifiers === null) {
            self::$identifiers = (new Set('string'))
                ->add('date')
                ->add('datetime');
        }

        return self::$identifiers;
    }
}
