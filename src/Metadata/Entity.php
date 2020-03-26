<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Immutable\Map;

interface Entity
{
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
     * @return Map<string, Property>
     */
    public function properties(): Map;

    /**
     * Return the class name of the entity
     */
    public function class(): ClassName;
}
