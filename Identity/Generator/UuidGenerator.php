<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity\Generator;

use Innmind\Neo4j\ONM\{
    Identity\Uuid,
    Identity\GeneratorInterface,
    IdentityInterface
};
use Innmind\Immutable\Map;
use Ramsey\Uuid\Uuid as Generator;

class UuidGenerator implements GeneratorInterface
{
    private $identities;

    public function __construct()
    {
        $this->identities = new Map('string', Uuid::class);
    }

    /**
     * {@inheritdoc}
     */
    public function new(): IdentityInterface
    {
        $uuid = new Uuid((string) Generator::uuid4());
        $this->identities = $this->identities->put(
            $uuid->value(),
            $uuid
        );

        return $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function knows($value): bool
    {
        return $this->identities->contains($value);
    }

    /**
     * {@inheritdoc}
     */
    public function get($value): IdentityInterface
    {
        return $this->identities->get($value);
    }

    /**
     * {@inheritdoc}
     */
    public function add(IdentityInterface $identity): GeneratorInterface
    {
        $this->identities = $this->identities->put(
            $identity->value(),
            $identity
        );

        return $this;
    }
}
