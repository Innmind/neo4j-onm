<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Identity,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class ChangesetComputer
{
    private $sources;

    public function __construct()
    {
        $this->sources = new Map(Identity::class, MapInterface::class);
    }

    /**
     * Use the given collection as the original data for the given entity
     *
     * @param MapInterface<string, mixed> $source
     */
    public function use(Identity $identity, MapInterface $source): self
    {
        if (
            (string) $source->keyType() !== 'string' ||
            (string) $source->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<string, mixed>');
        }

        $this->sources = $this->sources->put($identity, $source);

        return $this;
    }

    /**
     * Return the collection of data that has changed for the given identity
     *
     * @param MapInterface<string, mixed> $target
     *
     * @return MapInterface<string, mixed>
     */
    public function compute(Identity $identity, MapInterface $target): MapInterface
    {
        if (
            (string) $target->keyType() !== 'string' ||
            (string) $target->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<string, mixed>');
        }

        if (!$this->sources->contains($identity)) {
            return $target;
        }

        $source = $this->sources->get($identity);

        return $this->diff($source, $target);
    }

    private function diff(
        MapInterface $source,
        MapInterface $target
    ): MapInterface {
        $changeset = $target
            ->filter(static function(string $property, $value) use ($source): bool {
                if (
                    !$source->contains($property) ||
                    $value !== $source->get($property)
                ) {
                    return true;
                }

                return false;
            })
            ->reduce(
                new Map('string', 'mixed'),
                static function(MapInterface $carry, string $property, $value) use ($source): MapInterface {
                    return $carry->put($property, $value);
                }
            );

        return $source
            ->filter(static function(string $property) use ($target): bool {
                return !$target->contains($property);
            })
            ->reduce(
                $changeset,
                static function(Map $carry, string $property) use ($target): Map {
                    return $carry->put($property, null);
                }
            )
            ->map(function(string $property, $value) use ($source, $target) {
                if (!$value instanceof MapInterface) {
                    return $value;
                }

                return $this->diff(
                    $source->get($property),
                    $target->get($property)
                );
            })
            ->filter(static function(string $property, $value) {
                if (!$value instanceof MapInterface) {
                    return true;
                }

                return $value->size() !== 0;
            });
    }
}
