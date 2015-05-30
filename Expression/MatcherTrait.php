<?php

namespace Innmind\Neo4j\ONM\Expression;

trait MatcherTrait
{
    protected $variable;
    protected $alias;
    protected $params;
    protected $references;

    /**
     * {@inheritdoc}
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * {@inheritdoc}
     */
    public function hasVariable()
    {
        return !empty($this->variable);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias()
    {
        return !empty($this->alias);
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
        return !empty($this->params);
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersKey()
    {
        return sprintf(
            '%s_match_props',
            $this->variable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritdoc}
     */
    public function hasReferences()
    {
        return !empty($this->references);
    }
}
