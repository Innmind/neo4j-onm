<?php

namespace Innmind\Neo4j\ONM;

interface ExpressionInterface
{
    /**
     * Return the cypher string representation of the expression
     *
     * @return string
     */
    public function __toString();
}
