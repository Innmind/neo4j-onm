<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Expression\NodeMatchExpression;
use Innmind\Neo4j\ONM\Expression\RelationshipMatchExpression;
use Innmind\Neo4j\ONM\Expression\UpdateExpression;
use Innmind\Neo4j\ONM\Expression\CreateExpression;
use Innmind\Neo4j\ONM\Expression\RemoveExpression;
use Innmind\Neo4j\ONM\Expression\WhereExpression;
use Innmind\Neo4j\ONM\Expression\ReturnExpression;

class QueryBuilder
{
    /**
     * Return a new NodeMatchExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param string $alias Entity class alias (or class directly)
     * @param array $params Parameters used to match the node
     *
     * @return NodeMatchExpression
     */
    public function matchNode($variable = null, $alias = null, array $params = null)
    {
        return new NodeMatchExpression($variable, $alias, $params);
    }

    /**
     * Return a new RelationshipMatchExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param string $alias Entity class alias (or class directly)
     * @param array $params Parameters used to match the relationship
     *
     * @return RelationshipMatchExpression
     */
    public function matchRelationship($variable = null, $alias = null, array $params)
    {
        return new RelationshipMatchExpression($variable, $alias, $params);
    }

    /**
     * Return a new UpdateExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param array $params Data to update for the given variable
     *
     * @return UpdateExpression
     */
    public function update($variable, array $params)
    {
        return new UpdateExpression($variable, $params);
    }

    /**
     * Return a CreateExpression
     *
     * @param string $variable Variable name to be used in the cypher query
     * @param array $params Data to set in the new node
     *
     * @return CreateExpression
     */
    public function create($variable, array $params)
    {
        return new CreateExpression($variable, $params);
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
     * @param mixed $params
     *
     * @return WhereExpression
     */
    public function where($expr, $key = null, $params = null)
    {
        return new WhereExpression($expr, $key, $params);
    }

    /**
     * Return a CypherExpression
     *
     * @param string $cypher The cypher query
     * @param string $key Parameters key used in the cypher query
     * @param mixed $params
     *
     * @return CypherExpression
     */
    public function query($cypher, $key = null, $params = null)
    {
        return new CypherExpression($cypher, $key, $params);
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
