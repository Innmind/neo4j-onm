<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class UpdateExpression implements ParametrableExpressionInterface, ExpressionInterface
{
    protected $variable;
    protected $params;
    protected $references;

    public function __construct($variable, array $params, array $references = null)
    {
        $this->variable = (string) $variable;
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
