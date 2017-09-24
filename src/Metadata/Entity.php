<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Immutable\MapInterface;

interface Entity
{
    /**
     * Return the alias of the entity
     */
    public function alias(): Alias;

    /**
     * Return the repository definition
     */
    public function repository(): Repository;

    /**
     * Return the factory definition
     */
    public function factory(): Factory;

    /**
     * Return the id property
     */
    public function identity(): Identity;

    /**
     * Return the list of properties defined for this entity
     *
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface;

    /**
     * Return the class name of the entity
     */
    public function class(): ClassName;
}
