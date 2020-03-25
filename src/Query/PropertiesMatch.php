<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Immutable\Map;

final class PropertiesMatch
{
    private Map $properties;
    private Map $parameters;

    public function __construct(Map $properties, Map $parameters)
    {
        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== 'string'
        ) {
            throw new \TypeError('Argument 1 must be of type Map<string, string>');
        }

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type Map<string, mixed>');
        }

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
            $this->parameters()->merge($match->parameters())
        );
    }
}
