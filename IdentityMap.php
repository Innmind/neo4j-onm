<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Exception\IdentityException;

class IdentityMap
{
    protected $map = [];

    /**
     * Add an entity class name to the map
     *
     * @param string $class
     *
     * @return IdentityMap self
     */
    public function addClass($class)
    {
        $this->map[(string) $class] = (string) $class;

        return $this;
    }

    /**
     * Add an alias for the given class name
     *
     * @param string $alias
     * @param string $class
     *
     * @throws IdentityException If the alias is already used
     *
     * @return IdentityMap self
     */
    public function addAlias($alias, $class)
    {
        if (
            isset($this->map[(string) $alias]) &&
            $this->map[(string) $alias] !== (string) $class
        ) {
            throw new IdentityException(
                sprintf('The alias "%s" is already used', $alias),
                IdentityException::ALIAS_ALREADY_USED
            );
        }

        if (isset($this->map[(string) $class])) {
            unset($this->map[(string) $class]);
        }

        $this->map[(string) $alias] = (string) $class;

        return $this;
    }

    /**
     * Return the class name for the given alias
     *
     * @param string $alias
     *
     * @return string
     */
    public function getClass($alias)
    {
        if (in_array((string) $alias, $this->map, true)) {
            return $alias;
        }

        return $this->map[(string) $alias];
    }

    /**
     * Check if the given alias/class name exist in this map
     *
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->map[(string) $alias]) || in_array($alias, $this->map, true);
    }

    /**
     * Return the alias for the given class (or the class if it has no alias)
     *
     * @param string $class
     *
     * @return string
     */
    public function getAlias($class)
    {
        $alias = array_search((string) $class, $this->map);

        if ($alias === false) {
            return $class;
        }

        return $alias;
    }
}
