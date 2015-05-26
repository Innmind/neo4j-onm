<?php

namespace Innmind\Neo4j\ONM;

class Query
{
    protected $cypher;
    protected $params;

    public function __construct($cypher, array $params = null)
    {
        $this->cypher = (string) $cypher;
        $this->params = $params;
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
}
