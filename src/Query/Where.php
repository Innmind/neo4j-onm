<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Neo4j\ONM\Exception\DomainException;
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class Where
{
    private $cypher;
    private $parameters;

    public function __construct(string $cypher, MapInterface $parameters)
    {
        if (Str::of($cypher)->empty()) {
            throw new DomainException;
        }

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<string, mixed>');
        }

        $this->cypher = $cypher;
        $this->parameters = $parameters;
    }

    public function cypher(): string
    {
        return $this->cypher;
    }

    /**
     * @return MapInterface<string, mixed>
     */
    public function parameters(): MapInterface
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
