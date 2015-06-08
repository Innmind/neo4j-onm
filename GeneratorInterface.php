<?php

namespace Innmind\Neo4j\ONM;

interface GeneratorInterface
{
    /**
     * Generate a unique identifier (must be an int or a string)
     *
     * The unit of work is passed to give a chance to look in
     * the database to check if the generated id doesn't exist
     *
     * The entity available is the one the id will be generated for.
     * Do NOT try to inject the id in the entity, the variable is here
     * only to help you increase entropy (ie: as prefix for uniqid)
     *
     * @param UnitOfWork $uow
     * @param object $entity
     *
     * @return int|string
     */
    public function generate(UnitOfWork $uow, $entity);

    /**
     * Return the strategy it uses to generate a value
     *
     * @return string
     */
    public function getStrategy();
}
