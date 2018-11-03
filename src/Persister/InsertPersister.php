<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    Persister,
    Entity\Container,
    Entity\Container\State,
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Event\EntityAboutToBePersisted,
    Event\EntityPersisted,
    Identity,
    Metadata\Aggregate,
    Metadata\Property,
    Metadata\Child,
    Metadata\RelationshipEdge,
    Metadatas,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query,
    Clause\Expression\Relationship as DBALRelationship,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Stream,
    Str,
};

final class InsertPersister implements Persister
{
    private $changeset;
    private $dispatch;
    private $extract;
    private $metadata;
    private $name;
    private $variables;

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
        $this->name = new Str('e%s');
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Connection $connection, Container $container): void
    {
        $entities = $container->state(State::new());

        if ($entities->size() === 0) {
            return;
        }

        $entities->foreach(function(Identity $identity, $entity): void {
            ($this->dispatch)(new EntityAboutToBePersisted($identity, $entity));
        });

        $connection->execute($this->queryFor($entities));

        $entities->foreach(function(Identity $identity, object $entity) use ($container): void {
            $container->push($identity, $entity, State::managed());
            $this->changeset->use(
                $identity,
                ($this->extract)($entity)
            );
            ($this->dispatch)(new EntityPersisted($identity, $entity));
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

        $partitions = $entities->partition(function(Identity $identity, object $entity): bool {
            $meta = ($this->metadata)(\get_class($entity));

            return $meta instanceof Aggregate;
        });
        $query = $partitions
            ->get(true)
            ->reduce(
                $query,
                function(Query $carry, Identity $identity, object $entity): Query {
                    return $this->createAggregate($identity, $entity, $carry);
                }
            );
        $query = $partitions
            ->get(false)
            ->reduce(
                $query,
                function(Query $carry, Identity $identity, object $entity): Query {
                    return $this->createRelationship($identity, $entity, $carry);
                }
            );
        $this->variables = null;

        return $query;
    }

    /**
     * Add the cypher clause to create the node corresponding to the root of the aggregate
     */
    private function createAggregate(
        Identity $identity,
        object $entity,
        Query $query
    ): Query {
        $meta = ($this->metadata)(\get_class($entity));
        $data = ($this->extract)($entity);
        $varName = $this->name->sprintf(\md5($identity->value()));

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
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $paramKey,
                $data
                    ->filter(static function(string $key) use ($keysToKeep): bool {
                        return $keysToKeep->contains($key);
                    })
                    ->put(
                        $meta->identity()->property(),
                        $identity->value()
                    )
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            );

        $query = $meta
            ->children()
            ->reduce(
                $query,
                function(Query $carry, string $property, Child $child) use ($varName, $data): Query {
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
     * @param MapInterface<string, mixed> $data
     */
    private function createAggregateChild(
        Child $meta,
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
            ->create((string) $nodeName)
            ->linkedTo(
                (string) $endNodeName,
                $meta->labels()->toPrimitive()
            )
            ->withProperties($endNodeProperties->reduce(
                [],
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $endNodeParamKey,
                $data
                    ->get($meta->relationship()->childProperty())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
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
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $relationshipParamKey,
                $data
                    ->remove($meta->relationship()->childProperty())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
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
            static function(MapInterface $carry, string $property) use ($name): MapInterface {
                return $carry->put(
                    $property,
                    (string) $name->append($property)
                );
            }
        );
    }

    /**
     * Add the clause to create a relationship between nodes
     */
    private function createRelationship(
        Identity $identity,
        object $entity,
        Query $query
    ): Query {
        $meta = ($this->metadata)(\get_class($entity));
        $data = ($this->extract)($entity);
        $start = $data->get($meta->startNode()->property());
        $end = $data->get($meta->endNode()->property());
        $varName = $this->name->sprintf(\md5($identity->value()));
        $startName = $this->name->sprintf(\md5($start));
        $endName = $this->name->sprintf(\md5($end));

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
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                (string) $paramKey,
                $data
                    ->filter(static function(string $key) use ($keysToKeep): bool {
                        return $keysToKeep->contains($key);
                    })
                    ->put($meta->identity()->property(), $identity->value())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
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
     * @param mixed $value
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
