<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister,
    Entity\Container,
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Event\EntityAboutToBePersisted,
    Event\EntityPersisted,
    Identity,
    Metadata\Aggregate,
    Metadata\Property,
    Metadata\ValueObject,
    Metadata\RelationshipEdge,
    Metadatas
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query,
    Clause\Expression\Relationship as DBALRelationship
};
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Str,
    MapInterface,
    Stream,
    Map
};

final class InsertPersister implements Persister
{
    private $changeset;
    private $eventBus;
    private $extractor;
    private $metadatas;
    private $name;
    private $variables;

    public function __construct(
        ChangesetComputer $changeset,
        EventBusInterface $eventBus,
        DataExtractor $extractor,
        Metadatas $metadatas
    ) {
        $this->changeset = $changeset;
        $this->eventBus = $eventBus;
        $this->extractor = $extractor;
        $this->metadatas = $metadatas;
        $this->name = new Str('e%s');
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Connection $connection, Container $container): void
    {
        $entities = $container->state(Container::STATE_NEW);

        if ($entities->size() === 0) {
            return;
        }

        $entities->foreach(function(Identity $identity, $entity) {
            $this->eventBus->dispatch(
                new EntityAboutToBePersisted($identity, $entity)
            );
        });

        $connection->execute($this->queryFor($entities));

        $entities->foreach(function(
            Identity $identity,
            $entity
        ) use (
            $container
        ) {
            $container->push($identity, $entity, Container::STATE_MANAGED);
            $this->changeset->use(
                $identity,
                $this->extractor->extract($entity)
            );
            $this->eventBus->dispatch(
                new EntityPersisted($identity, $entity)
            );
        });
    }

    /**
     * Build the whole cypher query to insert at once all new nodes and relationships
     *
     * @param MapInterface<Identity, object> $entities
     */
    private function queryFor(MapInterface $entities): Query
    {
        $query = new Query\Query;
        $this->variables = new Stream('string');

        $partitions = $entities->partition(function(
            Identity $identity,
            $entity
        ) {
            $meta = $this->metadatas->get(get_class($entity));

            return $meta instanceof Aggregate;
        });
        $query = $partitions
            ->get(true)
            ->reduce(
                $query,
                function(Query $carry, Identity $identity, $entity): Query {
                    return $this->createAggregate($identity, $entity, $carry);
                }
            );
        $query = $partitions
            ->get(false)
            ->reduce(
                $query,
                function(Query $carry, Identity $identity, $entity): Query {
                    return $this->createRelationship($identity, $entity, $carry);
                }
            );
        $this->variables = null;

        return $query;
    }

    /**
     * Add the cypher clause to create the node corresponding to the root of the aggregate
     *
     * @param Identity $identity
     * @param object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function createAggregate(
        Identity $identity,
        $entity,
        Query $query
    ): Query {
        $meta = $this->metadatas->get(get_class($entity));
        $data = $this->extractor->extract($entity);
        $varName = $this->name->sprintf(md5($identity->value()));

        $query = $query->create(
            (string) $varName,
            $meta->labels()->toPrimitive()
        );
        $paramKey = $varName->append('_props');
        $properties = $this->buildProperties(
            $meta->properties(),
            $paramKey
        );
        $keysToKeep = $data->keys()->intersect($properties->keys());

        $query = $query
            ->withProperty(
                $meta->identity()->property(),
                (string) $paramKey
                    ->prepend('{')
                    ->append('}.')
                    ->append($meta->identity()->property())
            )
            ->withProperties($properties->reduce(
                [],
                function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $paramKey,
                $data
                    ->filter(function(string $key) use ($keysToKeep): bool {
                        return $keysToKeep->contains($key);
                    })
                    ->put(
                        $meta->identity()->property(),
                        $identity->value()
                    )
                    ->reduce(
                        [],
                        function(array $carry, string $key, $value): array {
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            );

        $query = $meta
            ->children()
            ->reduce(
                $query,
                function(Query $carry, string $property, ValueObject $child) use ($varName, $data): Query {
                    return $this->createAggregateChild(
                        $child,
                        $varName,
                        $data->get($property),
                        $carry
                    );
                }
            );
        $this->variables = $this->variables->add((string) $varName);

        return $query;
    }

    /**
     * Add the cypher clause to build the relationship and the node corresponding
     * to a child of the aggregate
     *
     * @param ValueObject $meta
     * @param Str $nodeName
     * @param MapInterface<string, mixed> $data
     * @param Query $query
     *
     * @return Query
     */
    private function createAggregateChild(
        ValueObject $meta,
        Str $nodeName,
        MapInterface $data,
        Query $query
    ): Query {
        $relationshipName = $nodeName
            ->append('_')
            ->append($meta->relationship()->property());
        $endNodeName = $relationshipName
            ->append('_')
            ->append($meta->relationship()->childProperty());
        $endNodeProperties = $this->buildProperties(
            $meta->properties(),
            $endNodeParamKey = $endNodeName->append('_props')
        );
        $relationshipProperties = $this->buildProperties(
            $meta->relationship()->properties(),
            $relationshipParamKey = $relationshipName->append('_props')
        );

        return $query
            ->create(
                (string) $nodeName
            )
            ->linkedTo(
                (string) $endNodeName,
                $meta->labels()->toPrimitive()
            )
            ->withProperties($endNodeProperties->reduce(
                [],
                function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $endNodeParamKey,
                $data
                    ->get(
                        $meta->relationship()->childProperty()
                    )
                    ->reduce(
                        [],
                        function(array $carry, string $key, $value): array {
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            )
            ->through(
                (string) $meta->relationship()->type(),
                (string) $relationshipName,
                DBALRelationship::LEFT
            )
            ->withProperties($relationshipProperties->reduce(
                [],
                function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $relationshipParamKey,
                $data
                    ->remove(
                        $meta->relationship()->childProperty()
                    )
                    ->reduce(
                        [],
                        function(array $carry, string $key, $value): array {
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            );
    }

    /**
     * Build the collection of properties to be injected in the query
     *
     * @param MapInterface<string, Property> $properties
     * @param Str $name
     *
     * @return MapInterface<string, string>
     */
    private function buildProperties(
        MapInterface $properties,
        Str $name
    ): MapInterface {
        $name = $name->prepend('{')->append('}.');

        return $properties->reduce(
            new Map('string', 'string'),
            function(Map $carry, string $property) use ($name): Map {
                return $carry->put(
                    $property,
                    (string) $name->append($property)
                );
            }
        );
    }

    /**
     * Add the clause to create a relationship between nodes
     *
     * @param Identity $identity
     * @param object $entity
     * @param Query $query
     *
     * @return Query
     */
    private function createRelationship(
        Identity $identity,
        $entity,
        Query $query
    ): Query {
        $meta = $this->metadatas->get(get_class($entity));
        $data = $this->extractor->extract($entity);
        $start = $data->get($meta->startNode()->property());
        $end = $data->get($meta->endNode()->property());
        $varName = $this->name->sprintf(md5($identity->value()));
        $startName = $this->name->sprintf(md5($start));
        $endName = $this->name->sprintf(md5($end));

        $paramKey = $varName->append('_props');
        $properties = $this->buildProperties($meta->properties(), $paramKey);
        $keysToKeep = $data->keys()->intersect($properties->keys());

        return $this
            ->matchEdge(
                $endName,
                $meta->endNode(),
                $end,
                $this->matchEdge(
                    $startName,
                    $meta->startNode(),
                    $start,
                    $query
                )
            )
            ->create((string) $startName)
            ->linkedTo((string) $endName)
            ->through(
                (string) $meta->type(),
                (string) $varName,
                DBALRelationship::RIGHT
            )
            ->withProperty(
                $meta->identity()->property(),
                (string) $paramKey
                    ->prepend('{')
                    ->append('}.')
                    ->append($meta->identity()->property())
            )
            ->withProperties($properties->reduce(
                [],
                function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $paramKey,
                $data
                    ->filter(function(string $key) use ($keysToKeep): bool {
                        return $keysToKeep->contains($key);
                    })
                    ->put($meta->identity()->property(), $identity->value())
                    ->reduce(
                        [],
                        function(array $carry, string $key, $value): array {
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            );
    }

    /**
     * Add the clause to match the target node in case it's node that is not
     * persisted via the current query
     *
     * @param Str $name
     * @param RelationshipEdge $meta
     * @param mixed $value
     * @param Query $query
     *
     * @return Query
     */
    private function matchEdge(
        Str $name,
        RelationshipEdge $meta,
        $value,
        Query $query
    ): Query {
        if ($this->variables->contains((string) $name)) {
            return $query;
        }

        if ($this->variables->size() > 0) {
            $query = $query->with(...$this->variables->toPrimitive());
        }

        $this->variables = $this->variables->add((string) $name);

        return $query
            ->match((string) $name)
            ->withProperty(
                $meta->target(),
                (string) $name
                    ->prepend('{')
                    ->append('_props}.')
                    ->append($meta->target())
            )
            ->withParameter(
                (string) $name->append('_props'),
                [
                    $meta->target() => $value,
                ]
            );
    }
}
