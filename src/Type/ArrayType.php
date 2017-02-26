<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Types,
    Exception\TypeDeclarationException,
    Exception\RecursiveTypeDeclarationException
};
use Innmind\Immutable\{
    MapInterface,
    Set,
    SetInterface
};

final class ArrayType implements TypeInterface
{
    private $nullable = false;
    private $inner;
    private static $identifiers;

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(MapInterface $config, Types $types): TypeInterface
    {
        $type = new self;

        if (!$config->contains('inner')) {
            throw TypeDeclarationException::missingField('inner');
        }

        if (self::identifiers()->contains($config->get('inner'))) {
            throw new RecursiveTypeDeclarationException;
        }

        $type->inner = $types->build(
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
        if (self::$identifiers === null) {
            self::$identifiers = (new Set('string'))->add('array');
        }

        return self::$identifiers;
    }
}
