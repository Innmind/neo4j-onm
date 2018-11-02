<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
    Exception\MissingFieldDeclaration,
    Exception\RecursiveTypeDeclaration,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class ArrayType implements Type
{
    private $nullable = false;
    private $inner;
    private static $identifiers;

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(MapInterface $config, Types $build): Type
    {
        $type = new self;

        if (!$config->contains('inner')) {
            throw new MissingFieldDeclaration('inner');
        }

        if (self::identifiers()->contains($config->get('inner'))) {
            throw new RecursiveTypeDeclaration;
        }

        $type->inner = $build(
            $config->get('inner'),
            $config
                ->remove('inner')
                ->remove('_types')
        );

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

        $array = [];

        foreach ($value as $sub) {
            $array[] = $this->inner->forDatabase($sub);
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        $array = [];

        foreach ($value as $sub) {
            $array[] = $this->inner->fromDatabase($sub);
        }

        return $array;
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
        return self::$identifiers ?? self::$identifiers = Set::of('string', 'array');
    }
}
