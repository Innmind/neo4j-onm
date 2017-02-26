<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\IdentityInterface;
use Innmind\Immutable\{
    MapInterface,
    Map
};

class ChangesetComputer
{
    private $sources;

    public function __construct()
    {
        $this->sources = new Map(
            IdentityInterface::class,
            MapInterface::class
        );
    }

    /**
     * Use the given collection as the original data for the given entity
     *
     * @param IdentityInterface $identity
     * @param MapInterface<string, mixed> $source
     *
     * @return self
     */
    public function use(
        IdentityInterface $identity,
        MapInterface $source
    ): self {
        $this->sources = $this->sources->put(
            $identity,
            $source
        );

        return $this;
    }

    /**
     * Return the collection of data that has changed for the given identity
     *
     * @param IdentityInterface $identity
     * @param MapInterface<string, mixed> $target
     *
     * @return MapInterface<string, mixed>
     */
    public function compute(
        IdentityInterface $identity,
        MapInterface $target
    ): MapInterface {
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
            ->filter(function(string $property, $value) use ($source): bool {
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
                function(Map $carry, string $property, $value) use ($source): Map {
                    return $carry->put($property, $value);
                }
            );

        return $source
            ->filter(function(string $property) use ($target): bool {
                return !$target->contains($property);
            })
            ->reduce(
                $changeset,
                function(Map $carry, string $property) use ($target): Map {
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
            ->filter(function(string $property, $value) {
                if (!$value instanceof MapInterface) {
                    return true;
                }

                return $value->size() !== 0;
            });
    }
}
