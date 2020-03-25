<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity\Generator;

use Innmind\Neo4j\ONM\{
    Identity\Uuid,
    Identity\Generator,
    Identity,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use Ramsey\Uuid\Uuid as Factory;

final class UuidGenerator implements Generator
{
    private Map $identities;

    public function __construct(string $type = Uuid::class)
    {
        if (Str::of($type)->empty()) {
            throw new DomainException;
        }

        $this->identities = Map::of('string', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function new(): Identity
    {
        $class = (string) $this->identities->valueType();
        $uuid = new $class((string) Factory::uuid4());
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
    public function get($value): Identity
    {
        return $this->identities->get($value);
    }

    /**
     * {@inheritdoc}
     */
    public function add(Identity $identity): Generator
    {
        $this->identities = $this->identities->put(
            $identity->value(),
            $identity
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function for($value): Identity
    {
        if ($this->knows($value)) {
            return $this->get($value);
        }

        $class = (string) $this->identities->valueType();
        $uuid = new $class($value);
        $this->add($uuid);

        return $uuid;
    }
}
