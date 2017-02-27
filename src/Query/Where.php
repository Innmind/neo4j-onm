<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Neo4j\ONM\Exception\InvalidArgumentException;
use Innmind\Immutable\MapInterface;

final class Where
{
    private $cypher;
    private $parameters;

    public function __construct(string $cypher, MapInterface $parameters)
    {
        if (
            empty($cypher) ||
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== 'mixed'
        ) {
            throw new InvalidArgumentException;
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
            sprintf('(%s AND %s)', $this->cypher(), $where->cypher()),
            $this->parameters()->merge($where->parameters())
        );
    }

    public function or(self $where): self
    {
        return new self(
            sprintf('(%s OR %s)', $this->cypher(), $where->cypher()),
            $this->parameters()->merge($where->parameters())
        );
    }

    public function not(): self
    {
        return new self(
            sprintf('NOT (%s)', $this->cypher()),
            $this->parameters()
        );
    }
}
