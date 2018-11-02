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
    Metadata\ValueObject,
    Metadata\Relationship,
    Metadatas,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class UpdatePersister implements Persister
{
    private $changeset;
    private $dispatch;
    private $extractor;
    private $metadatas;
    private $name;
    private $variables;

    public function __construct(
        ChangesetComputer $changeset,
        EventBus $dispatch,
        DataExtractor $extractor,
        Metadatas $metadatas
    ) {
        $this->changeset = $changeset;
        $this->dispatch = $dispatch;
        $this->extractor = $extractor;
        $this->metadatas = $metadatas;
        $this->name = new Str('e%s');
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Connection $connection, Container $container): void
    {
        $entities = $container->state(State::managed());
        $changesets = $entities->reduce(
            new Map(Identity::class, MapInterface::class),
            function(MapInterface $carry, Identity $identity, object $entity): MapInterface {
                $data = $this->extractor->extract($entity);
                $changeset = $this->changeset->compute($identity, $data);

                if ($changeset->size() === 0) {
                    return $carry;
                }

                return $carry->put($identity, $changeset);
            }
        );

        if ($changesets->size() === 0) {
            return;
        }

        $changesets->foreach(function(Identity $identity, MapInterface $changeset) use ($entities): void {
            ($this->dispatch)(
                new EntityAboutToBeUpdated(
                    $identity,
                    $entities->get($identity),
                    $changeset
                )
            );
        });

        $connection->execute($this->queryFor($changesets, $entities));

        $changesets->foreach(function(Identity $identity, MapInterface $changeset) use ($entities): void {
            $entity = $entities->get($identity);
            $this->changeset->use(
                $identity,
                $this->extractor->extract($entity)
            );
            ($this->dispatch)(
                new EntityUpdated(
                    $identity,
                    $entity,
                    $changeset
                )
            );
        });
    }

    /**
     * Build the query to update all entities at once
     *
     * @param MapInterface<Identity, MapInterface<string, mixed>> $changesets
     * @param MapInterface<Identity, object> $entities
     */
    private function queryFor(
        MapInterface $changesets,
        MapInterface $entities
    ): Query {
        $this->variables = new Map(Str::class, MapInterface::class);

        $query = $changesets->reduce(
            new Query\Query,
            function(Query $carry, Identity $identity, MapInterface $changeset) use ($entities): Query {
                $entity = $entities->get($identity);
                $meta = $this->metadatas->get(\get_class($entity));

                if ($meta instanceof Aggregate) {
                    return $this->matchAggregate(
                        $identity,
                        $entity,
                        $meta,
                        $changeset,
                        $carry
                    );
                } else if ($meta instanceof Relationship) {
                    return $this->matchRelationship(
                        $identity,
                        $entity,
                        $meta,
                        $changeset,
                        $carry
                    );
                }
            }
        );
        $query = $this
            ->variables
            ->reduce(
                $query,
                function(Query $carry, Str $variable, MapInterface $changeset): Query {
                    return $this->update($variable, $changeset, $carry);
                }
            );
        $this->variables = null;

        return $query;
    }

    /**
     * Add match clause to match all parts of the aggregate that needs to be updated
     *
     * @param MapInterface<string, mixed> $changeset
     */
    private function matchAggregate(
        Identity $identity,
        object $entity,
        Aggregate $meta,
        MapInterface $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(\md5($identity->value()));
        $query = $query
            ->match(
                (string) $name,
                $meta->labels()->toPrimitive()
            )
            ->withProperty(
                $meta->identity()->property(),
                (string) $name
                    ->prepend('{')
                    ->append('_identity}')
            )
            ->withParameter(
                (string) $name->append('_identity'),
                $identity->value()
            );
        $this->variables = $this->variables->put(
            $name,
            $this->buildProperties(
                $changeset,
                $meta->properties()
            )
        );

        return $meta
            ->children()
            ->filter(static function(string $property) use ($changeset): bool {
                return $changeset->contains($property);
            })
            ->reduce(
                $query,
                function(Query $carry, string $property, ValueObject $child) use ($changeset, $name): Query {
                    $changeset = $changeset->get($property);
                    $childName = null;
                    $relName = $name
                        ->append('_')
                        ->append($property);
                    $this->variables = $this->variables->put(
                        $relName,
                        $this->buildProperties(
                            $changeset,
                            $child->relationship()->properties()
                        )
                    );

                    if ($changeset->contains($child->relationship()->childProperty())) {
                        $childName = $relName
                            ->append('_')
                            ->append(
                                $child->relationship()->childProperty()
                            );
                        $this->variables = $this->variables->put(
                            $childName,
                            $changeset->get(
                                $child->relationship()->childProperty()
                            )
                        );
                    }

                    return $carry
                        ->match((string) $name)
                        ->linkedTo(
                            $childName ? (string) $childName : null,
                            $child->labels()->toPrimitive()
                        )
                        ->through(
                            (string) $child->relationship()->type(),
                            (string) $relName
                        );
                }
            );
    }

    /**
     * Add the match clause for a relationship
     *
     * @param MapInterface<string, mixed> $changeset
     */
    private function matchRelationship(
        Identity $identity,
        object $entity,
        Relationship $meta,
        MapInterface $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(\md5($identity->value()));
        $this->variables = $this->variables->put(
            $name,
            $this->buildProperties(
                $changeset,
                $meta->properties()
            )
        );

        return $query
            ->match()
            ->linkedTo()
            ->through(
                (string) $meta->type(),
                (string) $name
            )
            ->withProperty(
                $meta->identity()->property(),
                (string) $name
                    ->prepend('{')
                    ->append('_identity}')
            )
            ->withParameter(
                (string) $name->append('_identity'),
                $identity->value()
            );
    }

    /**
     * Build a collection with only the elements that are properties
     *
     * @param MapInterface<string, mixed> $changeset
     * @param MapInterface<string, Property> $properties
     *
     * @return MapInterface<string, mixed>
     */
    private function buildProperties(
        MapInterface $changeset,
        MapInterface $properties
    ): MapInterface {
        return $changeset->filter(static function(string $property) use ($properties) {
            return $properties->contains($property);
        });
    }

    /**
     * Add a clause to set all properties to be updated on the wished variable
     *
     * @param Str $variable
     * @param MapInterface<string, mixed> $changeset
     * @param Query $query
     *
     * @return Query
     */
    private function update(
        Str $variable,
        MapInterface $changeset,
        Query $query
    ): Query {
        return $query
            ->set(sprintf(
                '%s += {%s_props}',
                $variable,
                $variable
            ))
            ->withParameter(
                (string) $variable->append('_props'),
                $changeset->reduce(
                    [],
                    static function(array $carry, string $key, $value): array {
                        $carry[$key] = $value;

                        return $carry;
                    }
                )
            );
    }
}
