<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Expression\Builder;
use Innmind\Neo4j\ONM\Expression\NodeMatchExpression;
use Innmind\Neo4j\ONM\Expression\ParametrableExpressionInterface;
use Innmind\Neo4j\ONM\Expression\VariableAwareInterface;
use Innmind\Neo4j\DBAL\Query as DBALQuery;
use Innmind\Neo4j\DBAL\CypherBuilder;

class QueryBuilder
{
    protected $expr;
    protected $cypherBuilder;
    protected $sequence = [];
    protected static $builderMethods = [
        'Innmind\Neo4j\ONM\Expression\CreateExpression' => 'create',
        'Innmind\Neo4j\ONM\Expression\NodeMatchExpression' => 'match',
        'Innmind\Neo4j\ONM\Expression\RelationshipMatchExpression' => 'match',
        'Innmind\Neo4j\ONM\Expression\RemoveExpression' => 'remove',
        'Innmind\Neo4j\ONM\Expression\ReturnExpression' => 'setReturn',
        'Innmind\Neo4j\ONM\Expression\UpdateExpression' => 'set',
        'Innmind\Neo4j\ONM\Expression\WhereExpression' => 'where',
        'Innmind\Neo4j\ONM\Expression\OrderByExpression' => 'orderBy',
        'Innmind\Neo4j\ONM\Expression\SkipExpression' => 'skip',
        'Innmind\Neo4j\ONM\Expression\LimitExpression' => 'limit',
    ];

    public function __construct()
    {
        $this->expr = new Builder;
        $this->cypherBuilder = new CypherBuilder;
    }

    /**
     * Return the expression builder
     *
     * @return Builder
     */
    public function expr()
    {
        return $this->expr;
    }

    /**
     * Return a node match expression which is added to the cypher sequence
     *
     * @param string $variable
     * @param string $alias
     * @param array $params
     *
     * @return QueryBuilder self
     */
    public function matchNode($variable = null, $alias = null, array $params = null)
    {
        $expr = $this->expr->matchNode($variable, $alias, $params);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Add an expression to the query sequence
     *
     * @param ExpressionInterface $expr
     *
     * @return QueryBuilder self
     */
    public function addExpr(ExpressionInterface $expr)
    {
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Return a new update expression which is added to the cypher sequence
     *
     * @param string $variable
     * @param array $params
     *
     * @return QueryBuilder self
     */
    public function update($variable, array $params)
    {
        $expr = $this->expr->update($variable, $params);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Return a create expression which is added to the cypher sequence
     *
     * @param string $variable
     * @param string $alias
     * @param array $params
     *
     * @return QueryBuilder self
     */
    public function create($variable, $alias, array $params)
    {
        $expr = $this->expr->create($variable, $alias, $params);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Return a remove expression which is added to the cypher sequence
     *
     * @param string $variable
     *
     * @return QueryBuilder self
     */
    public function remove($variable)
    {
        $expr = $this->expr->remove($variable);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Return a where expression which is added to the cypher sequence
     *
     * @param string $expr
     * @param string $key
     * @param array $params
     * @param array $references
     *
     * @return QueryBuilder self
     */
    public function where($expr, $key = null, array $params = null, array $references = null)
    {
        $expr = $this->expr->where($expr, $key, $params, $references);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Create a return expression which is added to the cypher sequence
     *
     * @param string $return
     *
     * @return QueryBuilder self
     */
    public function toReturn($return)
    {
        $expr = $this->expr->returnExpr($return);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Create an order by expression which is added to the cypher sequence
     *
     * @param string $property
     * @param string $direction
     *
     * @return QueryBuilder self
     */
    public function orderBy($property, $direction = 'ASC')
    {
        $expr = $this->expr->orderBy($property, $direction);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Create a skip expression which is added to the cypher sequence
     *
     * @param int $value
     *
     * @return QueryBuilder
     */
    public function skip($value)
    {
        $expr = $this->expr->skip($value);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Create a limit expression which is added to the cypher sequence
     *
     * @param int $value
     *
     * @return QueryBuilder
     */
    public function limit($value)
    {
        $expr = $this->expr->limit($value);
        $this->sequence[] = $expr;

        return $this;
    }

    /**
     * Return an executable query
     *
     * @return Query
     */
    public function getQuery()
    {
        $dbalQuery = new DBALQuery;
        $query = new Query;

        foreach ($this->sequence as $element) {
            $string = (string) $element;

            $this->extractData($query, $element);

            if ($element instanceof NodeMatchExpression) {
                $subElement = $element;

                while ($subElement->hasRelationship()) {
                    $relationship = $subElement->getRelationship();
                    $subElement = $relationship->getNodeMatcher();

                    $this->extractData($query, $relationship);
                    $this->extractData($query, $subElement);
                }
            }

            $dbalQuery->{self::$builderMethods[get_class($element)]}($string);
        }

        $cypher = $this->cypherBuilder->getCypher($dbalQuery);
        $query->setCypher($cypher);

        return $query;
    }

    /**
     * Extract variables and parameters from the expression to inject them into the query
     *
     * @param Query $query
     * @param ExpressionInterface $expression
     *
     * @return void
     */
    protected function extractData(Query $query, ExpressionInterface $expression)
    {
        if (
            $expression instanceof ParametrableExpressionInterface &&
            $expression->hasParameters()
        ) {
            $query->addParameters(
                $expression->getParametersKey(),
                $expression->getParameters(),
                $expression->getReferences()
            );
        }

        if (
            $expression instanceof VariableAwareInterface &&
            $expression->hasVariable() &&
            $expression->hasAlias()
        ) {
            $query->addVariable(
                $expression->getVariable(),
                $expression->getAlias()
            );
        }
    }
}
