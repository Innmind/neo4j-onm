<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    Exception\InvalidArgumentException
};
use Innmind\Neo4j\DBAL\QueryInterface;
use Innmind\Immutable\MapInterface;

final class IdentityMatch
{
    private $query;
    private $variables;

    public function __construct(
        QueryInterface $query,
        MapInterface $variables
    ) {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== EntityInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        $this->query = $query;
        $this->variables = $variables;
    }

    public function query(): QueryInterface
    {
        return $this->query;
    }

    /**
     * @return MapInterface<string, EntityInterface>
     */
    public function variables(): MapInterface
    {
        return $this->variables;
    }
}
