<?php

namespace Innmind\Neo4j\ONM;

class Query
{
    protected $cypher;
    protected $params = [];
    protected $types = [];
    protected $variables = [];

    public function __construct($cypher = null)
    {
        $this->cypher = (string) $cypher;
    }

    /**
     * Set the cypher string
     *
     * @param string $cypher
     *
     * @return Query self
     */
    public function setCypher($cypher)
    {
        $this->cypher = (string) $cypher;

        return $this;
    }

    /**
     * Return the cypher string
     *
     * @return string
     */
    public function getCypher()
    {
        return $this->cypher;
    }

    /**
     * Add parameters to the query
     *
     * @param string $key
     * @param mixed $params
     * @param mixed $types Types associated to the parameters
     *
     * @return Query self
     */
    public function addParameters($key, $params, $types = null)
    {
        $this->params[(string) $key] = $params;

        if ($types !== null) {
            $this->types[(string) $key] = $types;
        }

        return $this;
    }

    /**
     * Check if the query has parameters
     *
     * @return bool
     */
    public function hasParameters()
    {
        return !empty($this->params);
    }

    /**
     * Return the parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Return the cypher string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getCypher();
    }

    /**
     * Register a variable with its entity alias used in the query
     *
     * @param string $variable
     * @param string $alias
     *
     * @return Query self
     */
    public function addVariable($variable, $alias)
    {
        $this->variables[(string) $variable] = (string) $alias;

        return $this;
    }

    /**
     * Check if variables are defined
     *
     * @return bool
     */
    public function hasVariables()
    {
        return !empty($this->variables);
    }

    /**
     * Return all the variables used in the query
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Check if the query has parameters types
     *
     * @return bool
     */
    public function hasTypes()
    {
        return !empty($this->types);
    }

    /**
     * Return the parameters types
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }
}
