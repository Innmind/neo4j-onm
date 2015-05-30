<?php

namespace Innmind\Neo4j\ONM;

class Query
{
    protected $cypher;
    protected $params = [];
    protected $references = [];
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
     * @param mixed $references References associated to the parameters
     *
     * @return Query self
     */
    public function addParameters($key, $params, $references = null)
    {
        $this->params[(string) $key] = $params;

        if ($references !== null) {
            $this->references[(string) $key] = $references;
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
     * Check if the query has parameters references
     *
     * @return bool
     */
    public function hasReferences()
    {
        return !empty($this->references);
    }

    /**
     * Return the parameters references
     *
     * @return array
     */
    public function getReferences()
    {
        return $this->references;
    }
}
