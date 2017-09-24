<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\Identity;

interface Generator
{
    public function new(): Identity;

    /**
     * Check if the generator already generated an identity with th given raw value
     *
     * @param mixed $value
     */
    public function knows($value): bool;

    /**
     * Return the identity object with the given raw value
     *
     * @param mixed $value
     */
    public function get($value): Identity;

    /**
     * Add the given identity to the known ones by this generator
     */
    public function add(Identity $identity): self;

    /**
     * Return an identity instance with the given value
     *
     * The difference with `get` is that it will create a new instance if none
     * known yet
     *
     * @param mixed $value
     */
    public function for($value): Identity;
}
