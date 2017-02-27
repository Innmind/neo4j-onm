<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    TypeInterface,
    Types,
    Exception\TypeDeclarationException,
    Exception\RecursiveTypeDeclarationException,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set
};

final class SetType implements TypeInterface
{
    private $nullable = false;
    private $inner;
    private $type;
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

        $innerConfig = $config->remove('inner');

        if ($config->contains('nullable')) {
            $type->nullable = true;
            $innerConfig = $innerConfig->remove('nullable');
        }

        $type->type = $config->contains('set_type') ?
            $config->get('set_type') : $config->get('inner');
        $type->inner = $types->build(
            $config->get('inner'),
            $innerConfig
        );

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

        if (
            !$value instanceof SetInterface ||
            (string) $value->type() !== $this->type
        ) {
            throw new InvalidArgumentException(sprintf(
                'The set must be an instance of SetInterface<%s>',
                $this->type
            ));
        }

        return $value
            ->map(function($value) {
                return $this->inner->forDatabase($value);
            })
            ->toPrimitive();
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value)
    {
        $set = new Set($this->type);

        foreach ($value as $sub) {
            $set = $set->add($this->inner->fromDatabase($sub));
        }

        return $set;
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
            self::$identifiers = (new Set('string'))->add('set');
        }

        return self::$identifiers;
    }
}
