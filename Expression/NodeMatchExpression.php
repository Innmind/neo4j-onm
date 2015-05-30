<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class NodeMatchExpression implements ParametrableExpressionInterface, VariableAwareInterface, ExpressionInterface
{
    use MatcherTrait;

    protected $relation;

    public function __construct($variable = null, $alias = null, array $params = null, array $types = null)
    {
        if (!empty($variable) && empty($alias)) {
            throw new \LogicException(
                'A node match can\'t be specified without the entity alias'
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
        $this->types = $types;
    }

    /**
     * Add that this node must be related to another node
     *
     * @param RelationshipMatchExpression $rel
     * @param NodeMatchExpression $node
     * @param string $direction
     *
     * @return NodeMatchExpression self
     */
    public function relatedTo(RelationshipMatchExpression $rel, NodeMatchExpression $node = null, $direction = 'right')
    {
        if ($node !== null) {
            $rel->setNodeMatcher($node);
        }

        $rel->setDirection($direction);

        $this->relation = $rel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $string = '(';

        if ($this->hasVariable()) {
            $string .= $this->variable;
        }

        if ($this->hasAlias()) {
            $string .= sprintf(
                ':%s',
                $this->alias
            );
        }

        if ($this->hasParameters()) {
            $string .= sprintf(' { %s }', $this->getParametersKey());
        }

        $string .= ')';

        if ($this->hasRelationship()) {
            $string .= (string) $this->relation;
        }

        return $string;
    }

    /**
     * Return the relation associated to the node match
     *
     * @return RelationshipMatchExpression
     */
    public function getRelationship()
    {
        return $this->relation;
    }

    /**
     * Check if the node match has a relationship specified
     *
     * @return bool
     */
    public function hasRelationship()
    {
        return !empty($this->relation);
    }
}
