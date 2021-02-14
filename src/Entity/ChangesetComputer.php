<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Identity,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class ChangesetComputer
{
    /** @var Map<Identity, Map<string, mixed>> */
    private Map $sources;

    public function __construct()
    {
        /** @var Map<Identity, Map<string, mixed>> */
        $this->sources = Map::of(Identity::class, Map::class);
    }

    /**
     * Use the given collection as the original data for the given entity
     *
     * @param Map<string, mixed> $source
     */
    public function use(Identity $identity, Map $source): void
    {
        assertMap('string', 'mixed', $source, 2);

        $this->sources = ($this->sources)($identity, $source);
    }

    /**
     * Return the collection of data that has changed for the given identity
     *
     * @param Map<string, mixed> $target
     *
     * @return Map<string, mixed>
     */
    public function compute(Identity $identity, Map $target): Map
    {
        assertMap('string', 'mixed', $target, 2);

        if (!$this->sources->contains($identity)) {
            return $target;
        }

        $source = $this->sources->get($identity);

        return $this->diff($source, $target);
    }

    /**
     * @param Map<string, mixed> $source
     * @param Map<string, mixed> $target
     *
     * @return Map<string, mixed>
     */
    private function diff(Map $source, Map $target): Map
    {
        $changeset = $target->filter(static function(string $property, $value) use ($source): bool {
            if (
                !$source->contains($property) ||
                $value !== $source->get($property)
            ) {
                return true;
            }

            return false;
        });

        /**
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Map<string, mixed>
         */
        return $source
            ->filter(static function(string $property) use ($target): bool {
                return !$target->contains($property);
            })
            ->reduce(
                $changeset,
                static function(Map $carry, string $property) use ($target): Map {
                    return ($carry)($property, null);
                },
            )
            ->map(function(string $property, $value) use ($source, $target) {
                if (!$value instanceof Map) {
                    return $value;
                }

                /** @psalm-suppress MixedArgument */
                return $this->diff(
                    $source->get($property),
                    $target->get($property),
                );
            })
            ->filter(static function(string $property, $value) {
                if (!$value instanceof Map) {
                    return true;
                }

                return !$value->empty();
            });
    }
}
