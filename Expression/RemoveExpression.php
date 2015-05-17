<?php

namespace Innmind\Neo4j\ONM\Expression;

class RemoveExpression
{
    protected $variable;

    public function __construct($variable)
    {
        $this->variable = (string) $variable;
    }

    public function __toString()
    {
        return $this->variable;
    }
}
