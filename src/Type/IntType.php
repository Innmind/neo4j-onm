<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class IntType implements Type
{
    private $nullable = false;
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
        return self::$identifiers ?? self::$identifiers = Set::of('string', 'int', 'integer');
    }
}
