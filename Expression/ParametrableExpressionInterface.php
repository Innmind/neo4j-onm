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
     * Return the references associated to the params
     *
     * @return array
     */
    public function getReferences();

    /**
     * Check if references are defined
     *
     * @return bool
     */
    public function hasReferences();
}
