<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Neo4j\DBAL\Query;
use Innmind\Immutable\Map;

final class IdentityMatch
{
    private Query $query;
    /** @var Map<string, Entity> */
    private Map $variables;

    /**
     * @param Map<string, Entity> $variables
     */
    public function __construct(Query $query, Map $variables)
    {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== Entity::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type Map<string, %s>',
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
     * @return Map<string, Entity>
     */
    public function variables(): Map
    {
        return $this->variables;
    }
}
