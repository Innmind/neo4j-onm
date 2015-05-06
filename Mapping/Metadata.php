<?php

namespace Innmind\Neo4j\ONM\Mapping;

/**
 * Represent all the metadata related to an entity
 */
abstract class Metadata
{
    protected $class;
    protected $repository;
    protected $properties = [];
    protected $id;
    protected $alias;

    /**
     * Set the class namespace of the entity
     *
     * @param string $class
     *
     * @return Metadata self
     */
    public function setClass($class)
    {
        $this->class = (string) $class;

        return $this;
    }

    /**
     * Return the entity class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the repository namespace associated to the entity
     *
     * @param string $repo
     *
     * @return Metadata self
     */
    public function setRepositoryClass($repo)
    {
        $this->repository = (string) $repo;

        return $this;
    }

    /**
     * Return the repository class
     *
     * @return string
     */
    public function getRepositoryClass()
    {
        return $this->repository;
    }

    /**
     * Add a property definition
     *
     * @param Property $prop
     *
     * @return Metadata self
     */
    public function addProperty(Property $prop)
    {
        $this->properties[$prop->getName()] = $prop;

        return $this;
    }

    /**
     * Return the properties definitions
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Specify the property name used as id
     *
     * @param Id $id
     *
     * @return Metadata self
     */
    public function setId(Id $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Return the property name used as id
     *
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the alias for the given class
     *
     * @param string $alias
     *
     * @return Metadata self
     */
    public function setAlias($alias)
    {
        $this->alias = (string) $alias;

        return $this;
    }

    /**
     * Check if the class has an alias
     *
     * @return bool
     */
    public function hasAlias()
    {
        return (bool) $this->alias;
    }

    /**
     * Return the class alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
