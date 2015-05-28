<?php

namespace Innmind\Neo4j\ONM\Expression;

interface ParametrableExpressionInterface
{
    /**
     * Return the params used to match the node
     *
     * @return array
     */
    public function getParameters();

    /**
     * Check if the match has parameters specified
     *
     * @return bool
     */
    public function hasParameters();

    /**
     * Return the key to identify parameters
     *
     * @return string
     */
    public function getParametersKey();

    /**
     * Return the types associated to the params
     *
     * @return array
     */
    public function getTypes();

    /**
     * Check if types are defined
     *
     * @return bool
     */
    public function hasTypes();
}
