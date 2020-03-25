<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Query;

use Innmind\Immutable\MapInterface;

final class PropertiesMatch
{
    private MapInterface $properties;
    private MapInterface $parameters;

    public function __construct(MapInterface $properties, MapInterface $parameters)
    {
        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== 'string'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, string>');
        }

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<string, mixed>');
        }

        $this->properties = $properties;
        $this->parameters = $parameters;
    }

    /**
     * @return MapInterface<string, string>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }

    /**
     * @return MapInterface<string, mixed>
     */
    public function parameters(): MapInterface
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
