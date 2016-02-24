<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Immutable\TypedCollectionInterface;

interface EntityInterface
{
    /**
     * Return the alias of the entity
     *
     * @return Alias
     */
    public function alias(): Alias;

    /**
     * Return the repository definition
     *
     * @return Repository
     */
    public function repository(): Repository;

    /**
     * Return the factory definition
     *
     * @return Factory
     */
    public function factory(): Factory;

    /**
     * Return the id property
     *
     * @return Identity
     */
    public function identity(): Identity;

    /**
     * Return the list of properties defined for this entity
     *
     * @return TypedCollectionInterface
     */
    public function properties(): TypedCollectionInterface;

    /**
     * Return the class name of the entity
     *
     * @return ClassName
     */
    public function class(): ClassName;
}
