<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class CreateExpression implements ParametrableExpressionInterface, VariableAwareInterface, ExpressionInterface
{
    protected $variable;
    protected $alias;
    protected $params;

    public function __construct($variable, $alias, array $params)
    {
        $this->variable = (string) $variable;
        $this->alias = (string) $alias;
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
            '%s_create_props',
            $this->variable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * {@inheritdoc}
     */
    public function hasVariable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf(
            '(%s:%s { %s })',
            $this->variable,
            $this->alias,
            $this->getParametersKey()
        );
    }
}
