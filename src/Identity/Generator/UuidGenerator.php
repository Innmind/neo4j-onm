<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Identity\Generator;

use Innmind\Neo4j\ONM\{
    Identity\Uuid,
    Identity\GeneratorInterface,
    IdentityInterface,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\Map;
use Ramsey\Uuid\Uuid as Generator;

final class UuidGenerator implements GeneratorInterface
{
    private $identities;

    public function __construct(string $type = Uuid::class)
    {
        if (empty($type)) {
            throw new InvalidArgumentException;
        }

        $this->identities = new Map('string', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function new(): IdentityInterface
    {
        $class = (string) $this->identities->valueType();
        $uuid = new $class((string) Generator::uuid4());
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

    /**
     * {@inheritdoc}
     */
    public function for($value): IdentityInterface
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
