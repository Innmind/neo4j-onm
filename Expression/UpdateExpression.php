<?php

namespace Innmind\Neo4j\ONM\Expression;

class UpdateExpression implements ParametrableExpressionInterface
{
    protected $variable;
    protected $params;

    public function __construct($variable, array $params)
    {
        $this->variable = (string) $variable;
        $this->params = (array) $params;
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
     * String representation of the update statement
     *
     * @return string
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
