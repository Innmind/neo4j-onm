<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class SkipExpression implements ExpressionInterface
{
    protected $value;

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->value = (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
