<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Type;

use Innmind\Neo4j\ONM\{
    Type,
    Types,
    Exception\MissingFieldDeclaration,
    Exception\RecursiveTypeDeclaration,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
};

final class SetType implements Type
{
    private $nullable = false;
    private $inner;
    private $type;
    private static $identifiers;

    public function __construct(Type $inner, string $type)
    {
        if ($inner instanceof self) {
            throw new RecursiveTypeDeclaration;
        }

        $this->inner = $inner;
        $this->type = $type;
    }

    public static function nullable(Type $inner, string $type): self
    {
        $self = new self($inner, $type);
        $self->nullable = true;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(MapInterface $config, Types $build): Type
    {
        if (!$config->contains('inner')) {
            throw new MissingFieldDeclaration('inner');
        }

        if (self::identifiers()->contains($config->get('inner'))) {
            throw new RecursiveTypeDeclaration;
        }

        $innerConfig = $config->remove('inner');

        $type = $config->contains('set_type') ?
            $config->get('set_type') : $config->get('inner');
        $inner = $build(
            $config->get('inner'),
            $innerConfig
        );

        if ($config->contains('nullable')) {
            return self::nullable($inner, $type);
        }

        return new self($inner, $type);
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

        return $value->reduce(
            [],
            function(array $carry, $value): array {
                $carry[] = $this->inner->forDatabase($value);

                return $carry;
            }
        );
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
        return self::$identifiers ?? self::$identifiers = Set::of('string', 'set');
    }
}
