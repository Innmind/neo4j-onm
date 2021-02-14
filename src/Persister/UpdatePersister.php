<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister,
    Entity\Container,
    Entity\Container\State,
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Identity,
    Event\EntityAboutToBeUpdated,
    Event\EntityUpdated,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Relationship,
    Metadata\Property,
    Metadatas,
    Exception\LogicException,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query\Query,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\unwrap;

final class UpdatePersister implements Persister
{
    private ChangesetComputer $changeset;
    private EventBus $dispatch;
    private DataExtractor $extract;
    private Metadatas $metadata;
    private Str $name;
    /** @var Map<Str, Map<string, mixed>> */
    private Map $variables;

    public function __construct(
        ChangesetComputer $changeset,
        EventBus $dispatch,
        DataExtractor $extract,
        Metadatas $metadata
    ) {
        $this->changeset = $changeset;
        $this->dispatch = $dispatch;
        $this->extract = $extract;
        $this->metadata = $metadata;
        $this->name = Str::of('e%s');
        $this->variables = Map::of(Str::class, Map::class);
    }

    public function __invoke(Connection $connection, Container $container): void
    {
        $entities = $container->state(State::managed());
        /** @var Map<Identity, Map<string, mixed>> */
        $changesets = $entities->reduce(
            Map::of(Identity::class, Map::class),
            function(Map $carry, Identity $identity, object $entity): Map {
                $data = ($this->extract)($entity);
                $changeset = $this->changeset->compute($identity, $data);

                if ($changeset->empty()) {
                    return $carry;
                }

                return ($carry)($identity, $changeset);
            }
        );

        if ($changesets->empty()) {
            return;
        }

        $changesets->foreach(function(Identity $identity, Map $changeset) use ($entities): void {
            ($this->dispatch)(
                new EntityAboutToBeUpdated(
                    $identity,
                    $entities->get($identity),
                    $changeset,
                ),
            );
        });

        $connection->execute($this->queryFor($changesets, $entities));

        $changesets->foreach(function(Identity $identity, Map $changeset) use ($entities): void {
            $entity = $entities->get($identity);
            $this->changeset->use(
                $identity,
                ($this->extract)($entity),
            );
            ($this->dispatch)(
                new EntityUpdated(
                    $identity,
                    $entity,
                    $changeset,
                ),
            );
        });
    }

    /**
     * Build the query to update all entities at once
     *
     * @param Map<Identity, Map<string, mixed>> $changesets
     * @param Map<Identity, object> $entities
     */
    private function queryFor(Map $changesets, Map $entities): Query
    {
        $this->variables = $this->variables->clear();

        $query = $changesets->reduce(
            new Query,
            function(Query $carry, Identity $identity, Map $changeset) use ($entities): Query {
                $entity = $entities->get($identity);
                $meta = ($this->metadata)(\get_class($entity));

                if ($meta instanceof Aggregate) {
                    return $this->matchAggregate(
                        $identity,
                        $entity,
                        $meta,
                        $changeset,
                        $carry,
                    );
                }

                if ($meta instanceof Relationship) {
                    return $this->matchRelationship(
                        $identity,
                        $entity,
                        $meta,
                        $changeset,
                        $carry,
                    );
                }

                $class = \get_class($meta);

                throw new LogicException("Unknown metadata '$class'");
            },
        );
        $query = $this
            ->variables
            ->reduce(
                $query,
                function(Query $carry, Str $variable, Map $changeset): Query {
                    return $this->update($variable, $changeset, $carry);
                },
            );
        $this->variables = $this->variables->clear();

        return $query;
    }

    /**
     * Add match clause to match all parts of the aggregate that needs to be updated
     *
     * @param Map<string, mixed> $changeset
     */
    private function matchAggregate(
        Identity $identity,
        object $entity,
        Aggregate $meta,
        Map $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(\md5($identity->toString()));
        $query = $query
            ->match(
                $name->toString(),
                ...unwrap($meta->labels()),
            )
            ->withProperty(
                $meta->identity()->property(),
                $name
                    ->prepend('$')
                    ->append('_identity')
                    ->toString(),
            )
            ->withParameter(
                $name->append('_identity')->toString(),
                $identity->value(),
            );
        $this->variables = ($this->variables)(
            $name,
            $this->buildProperties(
                $changeset,
                $meta->properties(),
            ),
        );

        return $meta
            ->children()
            ->filter(static function(string $property) use ($changeset): bool {
                return $changeset->contains($property);
            })
            ->reduce(
                $query,
                function(Query $carry, string $property, Child $child) use ($changeset, $name): Query {
                    /** @var Map<string, mixed> */
                    $changeset = $changeset->get($property);
                    $childName = null;
                    $relName = $name
                        ->append('_')
                        ->append($property);
                    $this->variables = ($this->variables)(
                        $relName,
                        $this->buildProperties(
                            $changeset,
                            $child->relationship()->properties(),
                        ),
                    );

                    if ($changeset->contains($child->relationship()->childProperty())) {
                        $childName = $relName
                            ->append('_')
                            ->append(
                                $child->relationship()->childProperty(),
                            );
                        /** @psalm-suppress MixedArgument */
                        $this->variables = ($this->variables)(
                            $childName,
                            $changeset->get(
                                $child->relationship()->childProperty(),
                            ),
                        );
                    }

                    return $carry
                        ->match($name->toString())
                        ->linkedTo(
                            $childName ? $childName->toString() : null,
                            ...unwrap($child->labels()),
                        )
                        ->through(
                            $child->relationship()->type()->toString(),
                            $relName->toString(),
                        );
                },
            );
    }

    /**
     * Add the match clause for a relationship
     *
     * @param Map<string, mixed|Map<string, mixed>> $changeset
     */
    private function matchRelationship(
        Identity $identity,
        object $entity,
        Relationship $meta,
        Map $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(\md5($identity->toString()));
        $this->variables = ($this->variables)(
            $name,
            $this->buildProperties(
                $changeset,
                $meta->properties(),
            ),
        );

        return $query
            ->match()
            ->linkedTo()
            ->through(
                $meta->type()->toString(),
                $name->toString(),
            )
            ->withProperty(
                $meta->identity()->property(),
                $name
                    ->prepend('$')
                    ->append('_identity')
                    ->toString(),
            )
            ->withParameter(
                $name->append('_identity')->toString(),
                $identity->value(),
            );
    }

    /**
     * Build a collection with only the elements that are properties
     *
     * @param Map<string, mixed> $changeset
     * @param Map<string, Property> $properties
     *
     * @return Map<string, mixed>
     */
    private function buildProperties(Map $changeset, Map $properties): Map
    {
        return $changeset->filter(static function(string $property) use ($properties) {
            return $properties->contains($property);
        });
    }

    /**
     * Add a clause to set all properties to be updated on the wished variable
     *
     * @param Map<string, mixed> $changeset
     */
    private function update(Str $variable, Map $changeset, Query $query): Query
    {
        return $query
            ->set(\sprintf(
                '%s += $%s_props',
                $variable->toString(),
                $variable->toString(),
            ))
            ->withParameter(
                $variable->append('_props')->toString(),
                $changeset->reduce(
                    [],
                    static function(array $carry, string $key, $value): array {
                        /** @psalm-suppress MixedAssignment */
                        $carry[$key] = $value;

                        return $carry;
                    },
                ),
            );
    }
}
