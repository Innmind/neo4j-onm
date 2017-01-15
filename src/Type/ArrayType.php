<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Exception\TypeDeclarationException,
    Exception\RecursiveTypeDeclarationException
};
use Innmind\Immutable\{
    CollectionInterface,
    Set,
    SetInterface
};

class ArrayType implements TypeInterface
{
    private $nullable = false;
    private $inner;
    private static $identifiers;

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(CollectionInterface $config): TypeInterface
    {
        $type = new self;

        if (!$config->hasKey('inner')) {
            throw TypeDeclarationException::missingField('inner');
        }

        if (self::identifiers()->contains($config->get('inner'))) {
            throw new RecursiveTypeDeclarationException;
        }

        $type->inner = $config
            ->get('_types')
            ->build(
                $config->get('inner'),
                $config
                    ->unset('inner')
                    ->unset('_types')
            );

        if ($config->hasKey('nullable')) {
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
