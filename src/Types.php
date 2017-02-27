<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Type\ArrayType,
    Type\SetType,
    Type\BooleanType,
    Type\DateType,
    Type\FloatType,
    Type\IntType,
    Type\StringType,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

final class Types
{
    private $types;

    public function __construct()
    {
        $defaults = [
            ArrayType::class,
            SetType::class,
            BooleanType::class,
            DateType::class,
            FloatType::class,
            IntType::class,
            StringType::class,
        ];
        $this->types = (new Map('string', 'string'));

        foreach ($defaults as $default) {
            $this->register($default);
        }
    }

    /**
     * Register the given type
     *
     * @param string $type FQCN
     *
     * @return self
     */
    public function register(string $type): self
    {
        $refl = new \ReflectionClass($type);

        if (!$refl->implementsInterface(TypeInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'The type "%s" must implement TypeInterface',
                $type
            ));
        }

        [$type, 'identifiers']()
            ->foreach(function(string $identifier) use ($type) {
                $this->types = $this->types->put(
                    $identifier,
                    $type
                );
            });

        return $this;
    }

    /**
     * Return the types mapping
     *
     * @return MapInterface<string, string>
     */
    public function all(): MapInterface
    {
        return $this->types;
    }

    /**
     * Build a new type instance of the wished type
     *
     * @param string $type
     * @param MapInterface<string, mixed> $config
     *
     * @return TypeInterface
     */
    public function build(string $type, MapInterface $config): TypeInterface
    {
        if (
            (string) $config->keyType() !== 'string' ||
            (string) $config->valueType() !== 'mixed'
        ) {
            throw new InvalidArgumentException;
        }

        return [$this->types->get($type), 'fromConfig']($config, $this);
    }
}
