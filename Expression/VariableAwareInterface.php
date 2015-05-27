<?php

namespace Innmind\Neo4j\ONM\Expression;

/**
 * Interface used to indentify expressions where a variable
 * can be associated to an entity alias
 */
interface VariableAwareInterface
{
    /**
     * Check if a variable is set
     *
     * @return bool
     */
    public function hasVariable();

    /**
     * Return the variable
     *
     * @return string
     */
    public function getVariable();

    /**
     * Check if an alias is set
     *
     * @return bool
     */
    public function hasAlias();

    /**
     * Return the alias
     *
     * @return string
     */
    public function getAlias();
}
