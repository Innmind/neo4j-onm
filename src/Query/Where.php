<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\{
    Map,
    Str,
};

final class Where
{
    private string $cypher;
    /** @var Map<string, mixed> */
    private Map $parameters;

    /**
     * @param Map<string, mixed> $parameters
     */
    public function __construct(string $cypher, Map $parameters)
    {
        if (Str::of($cypher)->empty()) {
            throw new DomainException;
        }

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type Map<string, mixed>');
        }

        $this->cypher = $cypher;
        $this->parameters = $parameters;
    }

    public function cypher(): string
    {
        return $this->cypher;
    }

    /**
     * @return Map<string, mixed>
     */
    public function parameters(): Map
    {
        return $this->parameters;
    }

    public function and(self $where): self
    {
        return new self(
            \sprintf('(%s AND %s)', $this->cypher(), $where->cypher()),
            $this->parameters()->merge($where->parameters())
        );
    }

    public function or(self $where): self
    {
        return new self(
            \sprintf('(%s OR %s)', $this->cypher(), $where->cypher()),
            $this->parameters()->merge($where->parameters())
        );
    }

    public function not(): self
    {
        return new self(
            \sprintf('NOT (%s)', $this->cypher()),
            $this->parameters()
        );
    }
}
