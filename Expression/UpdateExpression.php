<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class UpdateExpression implements ParametrableExpressionInterface, ExpressionInterface
{
    protected $variable;
    protected $params;
    protected $types;

    public function __construct($variable, array $params, array $types = null)
    {
        $this->variable = (string) $variable;
        $this->params = $params;
        $this->types = $types;
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersKey()
    {
        return sprintf(
            '%s_update_props',
            $this->variable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypes()
    {
        return !empty($this->types);
    }

    /**
     * Return the variable name
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Check if a variable name is specified
     *
     * @return bool
     */
    public function hasVariable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf(
            '%s += { %s }',
            $this->variable,
            $this->getParametersKey()
        );
    }
}
