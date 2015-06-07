<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class CreateRelationshipExpression implements ExpressionInterface, VariableAwareInterface, ParametrableExpressionInterface
{
    protected $startVar;
    protected $endVar;
    protected $variable;
    protected $alias;
    protected $params;
    protected $references = [];

    /**
     * @param string $startVar The variable that match the start node
     * @param string $endVar   The variable that match the end node
     * @param string $variable
     * @param string $alias
     * @param array  $params
     */
    public function __construct($startVar, $endVar, $variable, $alias, array $params)
    {
        $this->startVar = (string) $startVar;
        $this->endVar = (string) $endVar;
        $this->variable = (string) $variable;
        $this->alias = (string) $alias;
        $this->params = $params;

        foreach ($params as $key => $value) {
            $this->references[$key] = sprintf(
                '%s.%s',
                $variable,
                $key
            );
        }
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
    public function getVariable()
    {
        return $this->variable;
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
    public function getAlias()
    {
        return $this->alias;
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
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritdoc}
     */
    public function hasReferences()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf(
            '(%s)-[%s:%s { %s }]->(%s)',
            $this->startVar,
            $this->variable,
            $this->alias,
            $this->getParametersKey(),
            $this->endVar
        );
    }
}
