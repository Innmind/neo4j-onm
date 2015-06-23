<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class ReturnExpression implements ExpressionInterface
{
    protected $string;

    public function __construct($string)
    {
        $this->string = (string) $string;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->string;
    }
}
