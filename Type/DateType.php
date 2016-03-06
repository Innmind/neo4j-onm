<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\{
    CollectionInterface,
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
    public static function fromConfig(CollectionInterface $config): TypeInterface
    {
        $type = new self;

        if ($config->hasKey('nullable')) {
            $type->nullable = true;
        }

        if ($config->hasKey('format')) {
            $type->format = $config->get('format');
        }

        if ($config->hasKey('immutable')) {
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
            throw new InvalidTypeException(sprintf(
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
