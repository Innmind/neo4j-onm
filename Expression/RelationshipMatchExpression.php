<?php

namespace Innmind\Neo4j\ONM\Expression;

class RelationshipMatchExpression implements ParametrableExpressionInterface
{
    const DIRECTION_RIGHT = 'right';
    const DIRECTION_LEFT = 'left';
    const DIRECTION_NONE = 'none';

    protected $variable;
    protected $alias;
    protected $params;
    protected $node;
    protected $direction = 'right';

    public function __construct($variable = null, $alias = null, array $params = null)
    {
        if (!empty($variable) && empty($alias)) {
            throw new \LogicException(
                'A relationship match can\'t be specified without the entity alias'
            );
        }

        if (!empty($params) && empty($variable)) {
            throw new \LogicException(
                'Parameters to be matched can\'t be specified without a variable'
            );
        }

        $this->variable = (string) $variable;
        $this->alias = (string) $alias;
        $this->params = $params;

        $this->node = new NodeMatchExpression;
    }

    /**
     * Set the node matcher that will be rendered on the right relationship's side
     *
     * @param NodeMatchExpression $node
     *
     * @return RelationshipMatchExpression self
     */
    public function setNodeMatcher(NodeMatchExpression $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Set the relationship direction
     *
     * @param string $direction
     *
     * @return RelationshipMatchExpression self
     */
    public function setDirection($direction)
    {
        if (!in_array(
            (string) $direction,
            [self::DIRECTION_LEFT, self::DIRECTION_RIGHT, self::DIRECTION_NONE],
            true
        )) {
            throw new \InvalidArgumentException(
                'Relationship direction can be either right or left'
            );
        }

        $this->direction = (string) $direction;

        return $this;
    }

    /**
     * String representation of the relationship
     *
     * @return string
     */
    public function __toString()
    {
        $string = $this->direction === self::DIRECTION_LEFT ? '<-' : '-';
        $match = '';

        if ($this->hasVariable()) {
            $match = $this->variable;
        }

        if ($this->hasAlias()) {
            $match .= sprintf(
                ':%s',
                $this->alias
            );
        }

        if ($this->hasParameters()) {
            $match .= sprintf(
                ' { %s }',
                $this->getParametersKey()
            );
        }

        if (!empty($match)) {
            $string .= sprintf(
                '[%s]',
                $match
            );
        }

        $string .= $this->direction === self::DIRECTION_RIGHT ? '->' : '-';
        $string .= (string) $this->node;

        return $string;
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
        return !empty($this->variable);
    }

    /**
     * Return the node alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Check is an alias is set
     *
     * @return bool
     */
    public function hasAlias()
    {
        return !empty($this->alias);
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
     * {@inheritdoc}
     */
    public function getParametersKey()
    {
        return sprintf(
            '%s_match_props',
            $this->variable
        );
    }

    /**
     * Return the node matcher
     *
     * @return NodeMatchExpression
     */
    public function getNodeMatcher()
    {
        return $this->node;
    }
}
