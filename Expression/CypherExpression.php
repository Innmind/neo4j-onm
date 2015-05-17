<?php

namespace Innmind\Neo4j\ONM\Expression;

class CypherExpression implements ParametrableExpressionInterface
{
    protected $cypher;
    protected $key;
    protected $params;

    public function __construct($cypher, $key = null, $params = null)
    {
        if ($params !== null && empty($key)) {
            throw new \LogicException(
                'Where expression parameters can\'t be used without a key name'
            );
        }

        $this->cypher = (string) $cypher;
        $this->key = (string) $key;
        $this->params = $params;
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
        return $this->params !== null;
    }

    /**
     * {@inehritdoc}
     */
    public function getParametersKey()
    {
        return $this->key;
    }

    /**
     * Return the cypher query
     *
     * @return string
     */
    public function __toString()
    {
        return $this->cypher;
    }
}
