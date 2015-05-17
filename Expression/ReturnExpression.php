<?php

namespace Innmind\Neo4j\ONM\Expression;

class ReturnExpression
{
    protected $string;

    public function __construct($string)
    {
        $this->string = (string) $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}
