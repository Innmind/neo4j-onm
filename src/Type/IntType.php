<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Types
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    SetInterface
};

final class IntType implements TypeInterface
{
    private $nullable = false;
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

        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        return (int) $value;
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
                ->add('int')
                ->add('integer');
        }

        return self::$identifiers;
    }
}
