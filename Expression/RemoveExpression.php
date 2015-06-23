<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class RemoveExpression implements ExpressionInterface
{
    protected $variable;

    public function __construct($variable)
    {
        $this->variable = (string) $variable;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->variable;
    }
}
