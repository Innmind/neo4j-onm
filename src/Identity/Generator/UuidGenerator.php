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

    public function new(): Identity
    {
        /** @var class-string<Uuid> */
        $class = $this->identities->valueType();
        $uuid = new $class(Factory::uuid4()->toString());
        $this->identities = ($this->identities)(
            $uuid->toString(),
            $uuid,
        );

        return $uuid;
    }

    public function knows($value): bool
    {
        /** @psalm-suppress MixedArgument */
        return $this->identities->contains($value);
    }

    public function get($value): Identity
    {
        /** @psalm-suppress MixedArgument */
        return $this->identities->get($value);
    }

    public function add(Identity $identity): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->identities = ($this->identities)(
            $identity->toString(),
            $identity,
        );
    }

    public function for($value): Identity
    {
        if ($this->knows($value)) {
            return $this->get($value);
        }

        /** @var class-string<Uuid> */
        $class = $this->identities->valueType();
        $uuid = new $class($value);
        $this->add($uuid);

        return $uuid;
    }
}
