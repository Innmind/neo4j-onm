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
    /** @var Map<string, Uuid> */
    private Map $identities;

    /**
     * @param class-string<Uuid> $type
     */
    public function __construct(string $type = Uuid::class)
    {
        if (Str::of($type)->empty()) {
            throw new DomainException;
        }

        /** @var Map<string, Uuid> */
        $this->identities = Map::of('string', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function new(): Identity
    {
        /** @var class-string<Uuid> */
        $class = (string) $this->identities->valueType();
        $uuid = new $class((string) Factory::uuid4());
        $this->identities = $this->identities->put(
            $uuid->toString(),
            $uuid
        );

        return $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function knows($value): bool
    {
        /** @psalm-suppress MixedArgument */
        return $this->identities->contains($value);
    }

    /**
     * {@inheritdoc}
     */
    public function get($value): Identity
    {
        /** @psalm-suppress MixedArgument */
        return $this->identities->get($value);
    }

    /**
     * {@inheritdoc}
     */
    public function add(Identity $identity): Generator
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->identities = $this->identities->put(
            $identity->toString(),
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

        /** @var class-string<Uuid> */
        $class = (string) $this->identities->valueType();
        $uuid = new $class($value);
        $this->add($uuid);

        return $uuid;
    }
}
