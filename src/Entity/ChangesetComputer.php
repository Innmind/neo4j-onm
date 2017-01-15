<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\IdentityInterface;
use Innmind\Immutable\{
    Map,
    CollectionInterface,
    Collection
};

class ChangesetComputer
{
    private $sources;

    public function __construct()
    {
        $this->sources = new Map(
            IdentityInterface::class,
            CollectionInterface::class
        );
    }

    /**
     * Use the given collection as the original data for the given entity
     *
     * @param IdentityInterface $identity
     * @param CollectionInterface $source
     *
     * @return self
     */
    public function use(
        IdentityInterface $identity,
        CollectionInterface $source
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
     * @param CollectionInterface $target
     *
     * @return CollectionInterface
     */
    public function compute(
        IdentityInterface $identity,
        CollectionInterface $target
    ): CollectionInterface {
        if (!$this->sources->contains($identity)) {
            return $target;
        }

        $source = $this->sources->get($identity);

        return $this->diff($source, $target);
    }

    private function diff(
        CollectionInterface $source,
        CollectionInterface $target
    ): CollectionInterface {
        $changeset = new Collection([]);

        $target->each(function(
            string $property,
            $value
        ) use (
            &$changeset,
            $source
        ) {
            if (
                !$source->hasKey($property) ||
                $value !== $source->get($property)
            ) {
                $changeset = $changeset->set(
                    $property,
                    $value
                );
            }
        });

        $source->each(function(string $property) use (&$changeset, $target) {
            if (!$target->hasKey($property)) {
                $changeset = $changeset->set($property, null);
            }
        });

        $changeset->each(function(
            string $property,
            $value
        ) use (
            &$changeset,
            $source,
            $target
        ) {
            if (!$value instanceof CollectionInterface) {
                return;
            }

            $changeset = $changeset->set(
                $property,
                $this->diff(
                    $source->get($property),
                    $target->get($property)
                )
            );
        });
        $changeset = $changeset->filter(function($value) {
            if (!$value instanceof CollectionInterface) {
                return true;
            }

            return $value->count() !== 0;
        });

        return $changeset;
    }
}
