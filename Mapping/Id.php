<?php

namespace Innmind\Neo4j\ONM\Mapping;

class Id
{
    protected $property;
    protected $type;
    protected $strategy;

    /**
     * Set the property name used as id
     *
     * @param string $name
     *
     * @return Id self
     */
    public function setProperty($name)
    {
        $this->property = (string) $name;

        return $this;
    }

    /**
     * Return the property name
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set the id data type
     *
     * @param string $type
     *
     * @return Id self
     */
    public function setType($type)
    {
        $this->type = (string) $type;

        return $this;
    }

    /**
     * Return the data type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the strategy used to generate the id value
     *
     * @param string $strategy
     *
     * @return Id self
     */
    public function setStrategy($strategy)
    {
        $this->strategy = (string) $strategy;

        return $this;
    }

    /**
     * Return the strategy
     *
     * @return string
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
