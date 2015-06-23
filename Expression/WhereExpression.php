<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class WhereExpression implements ParametrableExpressionInterface, ExpressionInterface
{
    protected $expr;
    protected $key;
    protected $params;
    protected $references;

    public function __construct($expr, $key = null, array $params = null, array $references = null)
    {
        if ($params !== null && empty($key)) {
            throw new \LogicException(
                'Where expression parameters can\'t be used without a key name'
            );
        }

        $this->expr = (string) $expr;
        $this->key = (string) $key;
        $this->params = $params;
        $this->references = $references;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameters()
    {
        return !empty($this->params);
    }

    /**
     * {@inehritdoc}
     */
    public function getParametersKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritdoc}
     */
    public function hasReferences()
    {
        return !empty($this->references);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->expr;
    }
}
