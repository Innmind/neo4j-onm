<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Immutable\CollectionInterface;
use Innmind\Immutable\SetInterface;

interface TypeInterface
{
    /**
     * Build a type instance out of a config array
     *
     * @param CollectionInterface $config
     *
     * @return self
     */
    public static function fromConfig(CollectionInterface $config): self;

    /**
     * Format the given value as a valid database value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function forDatabase($value);

    /**
     * Format the given value as a valid PHP value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function fromDatabase($value);

    /**
     * Return the identifiers that can be used to reference the type class
     *
     * @return SetInterface<string>
     */
    public static function identifiers(): SetInterface;
}
