<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\{
    Identity,
    Exception\DomainException
};
use Innmind\Immutable\Str;

class Uuid implements Identity
{
    const PATTERN = '/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/';

    private $value;

    public function __construct(string $uuid)
    {
        if (!(new Str($uuid))->matches(self::PATTERN)) {
            throw new DomainException;
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
