<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity\Container;

final class State
{
    private const MANAGED = 1;
    private const NEW = 2;
    private const TO_BE_REMOVED = 3;
    private const REMOVED = 4;

    private static $managed;
    private static $new;
    private static $toBeRemoved;
    private static $removed;

    private $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function managed(): self
    {
        return self::$managed ?? self::$managed = new self(self::MANAGED);
    }
    public static function new(): self
    {
        return self::$new ?? self::$new = new self(self::NEW);
    }
    public static function toBeRemoved(): self
    {
        return self::$toBeRemoved ?? self::$toBeRemoved = new self(self::TO_BE_REMOVED);
    }
    public static function removed(): self
    {
        return self::$removed ?? self::$removed = new self(self::REMOVED);
    }
}
