<?php

namespace Innmind\Neo4j\ONM\Expression;

class Builder
{
    /**
     * Return a new NodeMatchExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param string $alias Entity class alias (or class directly)
     * @param array $params Parameters used to match the node
     * @param array $references
     *
     * @return NodeMatchExpression
     */
    public function matchNode($variable = null, $alias = null, array $params = null, array $references = null)
    {
        return new NodeMatchExpression($variable, $alias, $params, $references);
    }

    /**
     * Return a new RelationshipMatchExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param string $alias Entity class alias (or class directly)
     * @param array $params Parameters used to match the relationship
     * @param array $references
     *
     * @return RelationshipMatchExpression
     */
    public function matchRelationship($variable = null, $alias = null, array $params = null, array $references = null)
    {
        return new RelationshipMatchExpression($variable, $alias, $params, $references);
    }

    /**
     * Return a new UpdateExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param array $params Data to update for the given variable
     * @param array $references
     *
     * @return UpdateExpression
     */
    public function update($variable, array $params, array $references = null)
    {
        return new UpdateExpression($variable, $params, $references);
    }

    /**
     * Return a CreateExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param string $alias Entity alias for the node
     * @param array $params Data to set in the new node
     *
     * @return CreateExpression
     */
    public function create($variable, $alias, array $params)
    {
        return new CreateExpression($variable, $alias, $params);
    }

    /**
     * Return a RemoveExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     *
     * @return RemoveExpression
     */
    public function remove($variable)
    {
        return new RemoveExpression($variable);
    }

    /**
     * Return a WhereExpression
     *
     * @param string $expr The where expression
     * @param string $key Parameters key used in the cypher query
     * @param array $params
     * @param array $references
     *
     * @return WhereExpression
     */
    public function where($expr, $key = null, array $params = null, array $references = null)
    {
        return new WhereExpression($expr, $key, $params, $references);
    }

    /**
     * Create a ReturnExpression
     *
     * @param string $return
     *
     * @return ReturnExpression
     */
    public function returnExpr($return)
    {
        return new ReturnExpression($return);
    }
}
