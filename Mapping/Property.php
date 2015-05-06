<?php

namespace Innmind\Neo4j\ONM\Mapping;

class Property
{
    protected $name;
    protected $type = 'string';
    protected $nullable = true;
    protected $options = [];

    /**
     * Set the property name
     *
     * @param string $name
     *
     * @return Property self
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Return the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the property type
     *
     * @param string $type
     *
     * @return Property self
     */
    public function setType($type)
    {
        $this->type = (string) $type;

        return $this;
    }

    /**
     * Return the property type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set if the property can be a null value
     *
     * @param bool $nullable
     *
     * @return Property self
     */
    public function setNullable($nullable = true)
    {
        $this->nullable = (bool) $nullable;

        return $this;
    }

    /**
     * Check if the property is nullable
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Add an option
     *
     * @param string $name
     * @param mixed $value
     *
     * @return Property self
     */
    public function addOption($name, $value)
    {
        $this->options[(string) $name] = $value;

        return $this;
    }

    /**
     * Check if an option is defined
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->options[(string) $name]);
    }

    /**
     * Return an option value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->options[(string) $name];
    }
}
