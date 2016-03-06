<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\IdentityInterface;

interface GeneratorInterface
{
    public function new(): IdentityInterface;

    /**
     * Check if the generator already generated an identity with th given raw value
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function knows($value): bool;

    /**
     * Return the identity object with the given raw value
     *
     * @param mixed $value
     *
     * @return IdentityInterface
     */
    public function get($value): IdentityInterface;

    /**
     * Add the given identity to the known ones by this geenrator
     *
     * @param IdentityInterface $identity
     *
     * @return self
     */
    public function add(IdentityInterface $identity): self;
}
