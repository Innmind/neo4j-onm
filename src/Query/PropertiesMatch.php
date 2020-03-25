<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class PropertiesMatch
{
    /** @var Map<string, string> */
    private Map $properties;
    /** @var Map<string, mixed> */
    private Map $parameters;

    /**
     * @param Map<string, string> $properties
     * @param Map<string, mixed> $parameters
     */
    public function __construct(Map $properties, Map $parameters)
    {
        assertMap('string', 'string', $properties, 1);
        assertMap('string', 'mixed', $parameters, 2);

        $this->properties = $properties;
        $this->parameters = $parameters;
    }

    /**
     * @return Map<string, string>
     */
    public function properties(): Map
    {
        return $this->properties;
    }

    /**
     * @return Map<string, mixed>
     */
    public function parameters(): Map
    {
        return $this->parameters;
    }

    public function merge(self $match): self
    {
        return new self(
            $this->properties()->merge($match->properties()),
            $this->parameters()->merge($match->parameters()),
        );
    }
}
