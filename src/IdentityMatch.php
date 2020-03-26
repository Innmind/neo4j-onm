<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Metadata\Entity;
use Innmind\Neo4j\DBAL\Query;
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

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
        assertMap('string', Entity::class, $variables, 2);

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
