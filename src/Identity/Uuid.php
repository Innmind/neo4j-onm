<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\Str;

class Uuid implements IdentityInterface
{
    const PATTERN = '/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/';

    private $value;

    public function __construct(string $uuid)
    {
        if (!(new Str($uuid))->matches(self::PATTERN)) {
            throw new InvalidArgumentException;
        }

        $this->value = $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
