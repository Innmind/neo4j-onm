<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Neo4j\DBAL\Query;
use Innmind\Immutable\MapInterface;

final class IdentityMatch
{
    private $query;
    private $variables;

    public function __construct(
        Query $query,
        MapInterface $variables
    ) {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== Entity::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                Entity::class
            ));
        }

        $this->query = $query;
        $this->variables = $variables;
    }

    public function query(): Query
    {
        return $this->query;
    }

    /**
     * @return MapInterface<string, Entity>
     */
    public function variables(): MapInterface
    {
        return $this->variables;
    }
}
