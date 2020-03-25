<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\{
    Identity,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

class Uuid implements Identity
{
    private const PATTERN = '/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/';

    private string $value;

    public function __construct(string $uuid)
    {
        if (!Str::of($uuid)->matches(self::PATTERN)) {
            throw new DomainException;
        }

        $this->value = $uuid;
    }

    public function value()
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
