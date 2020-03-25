<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\Container;

final class State
{
    private const MANAGED = 1;
    private const NEW = 2;
    private const TO_BE_REMOVED = 3;
    private const REMOVED = 4;

    private static ?self $managed = null;
    private static ?self $new = null;
    private static ?self $toBeRemoved = null;
    private static ?self $removed = null;

    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function managed(): self
    {
        return self::$managed ??= new self(self::MANAGED);
    }
    public static function new(): self
    {
        return self::$new ??= new self(self::NEW);
    }
    public static function toBeRemoved(): self
    {
        return self::$toBeRemoved ??= new self(self::TO_BE_REMOVED);
    }
    public static function removed(): self
    {
        return self::$removed ??= new self(self::REMOVED);
    }
}
