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
    Metadata\Aggregate\Child,
    Metadata\RelationshipEdge,
    Metadata\Relationship,
    Metadatas,
};
use Innmind\Neo4j\DBAL\{
    Connection,
    Query\Query,
};
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\unwrap;

final class InsertPersister implements Persister
{
    private ChangesetComputer $changeset;
    private EventBus $dispatch;
    private DataExtractor $extract;
    private Metadatas $metadata;
    private Str $name;
    /** @var Sequence<string> */
    private Sequence $variables;

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
        $this->variables = Sequence::strings();
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
     * @param Map<Identity, object> $entities
     */
    private function queryFor(Map $entities): Query
    {
        $query = new Query;
        $this->variables = $this->variables->clear();

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
        $this->variables = $this->variables->clear();

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
        /** @var Aggregate */
        $meta = ($this->metadata)(\get_class($entity));
        $data = ($this->extract)($entity);
        $varName = $this->name->sprintf(\md5($identity->toString()));

        $query = $query->create(
            $varName->toString(),
            ...unwrap($meta->labels()),
        );
        $paramKey = $varName->append('_props');
        $properties = $this->buildProperties(
            $meta->properties(),
            $paramKey
        );
        $keysToKeep = $data->keys()->intersect($properties->keys());

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $query = $query
            ->withProperty(
                $meta->identity()->property(),
                $paramKey
                    ->prepend('{')
                    ->append('}.')
                    ->append($meta->identity()->property())
                    ->toString()
            )
            ->withProperties($properties->reduce(
                [],
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                $paramKey->toString(),
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
                            /** @psalm-suppress MixedAssignment */
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
                    /** @psalm-suppress MixedArgument */
                    return $this->createAggregateChild(
                        $child,
                        $varName,
                        $data->get($property),
                        $carry
                    );
                }
            );
        $this->variables = $this->variables->add($varName->toString());

        return $query;
    }

    /**
     * Add the cypher clause to build the relationship and the node corresponding
     * to a child of the aggregate
     *
     * @param Map<string, mixed> $data
     */
    private function createAggregateChild(
        Child $meta,
        Str $nodeName,
        Map $data,
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

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedMethodCall
         */
        return $query
            ->create($nodeName->toString())
            ->linkedTo(
                $endNodeName->toString(),
                ...unwrap($meta->labels()),
            )
            ->withProperties($endNodeProperties->reduce(
                [],
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                $endNodeParamKey->toString(),
                $data
                    ->get($meta->relationship()->childProperty())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
                            /** @psalm-suppress MixedAssignment */
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            )
            ->through(
                $meta->relationship()->type()->toString(),
                $relationshipName->toString(),
                'left'
            )
            ->withProperties($relationshipProperties->reduce(
                [],
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                $relationshipParamKey->toString(),
                $data
                    ->remove($meta->relationship()->childProperty())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
                            /** @psalm-suppress MixedAssignment */
                            $carry[$key] = $value;

                            return $carry;
                        }
                    )
            );
    }

    /**
     * Build the collection of properties to be injected in the query
     *
     * @param Map<string, Property> $properties
     *
     * @return Map<string, string>
     */
    private function buildProperties(
        Map $properties,
        Str $name
    ): Map {
        $name = $name->prepend('{')->append('}.');

        /** @var Map<string, string> */
        return $properties->reduce(
            Map::of('string', 'string'),
            static function(Map $carry, string $property) use ($name): Map {
                return $carry->put(
                    $property,
                    $name->append($property)->toString(),
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
        /** @var Relationship */
        $meta = ($this->metadata)(\get_class($entity));
        $data = ($this->extract)($entity);
        /** @var mixed */
        $start = $data->get($meta->startNode()->property());
        /** @var mixed */
        $end = $data->get($meta->endNode()->property());
        $varName = $this->name->sprintf(\md5($identity->toString()));
        $startName = $this->name->sprintf(\md5((string) $start));
        $endName = $this->name->sprintf(\md5((string) $end));

        $paramKey = $varName->append('_props');
        $properties = $this->buildProperties($meta->properties(), $paramKey);
        $keysToKeep = $data->keys()->intersect($properties->keys());

        /** @psalm-suppress MixedArgumentTypeCoercion */
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
            ->create($startName->toString())
            ->linkedTo($endName->toString())
            ->through(
                $meta->type()->toString(),
                $varName->toString(),
                'right'
            )
            ->withProperty(
                $meta->identity()->property(),
                $paramKey
                    ->prepend('{')
                    ->append('}.')
                    ->append($meta->identity()->property())
                    ->toString(),
            )
            ->withProperties($properties->reduce(
                [],
                static function(array $carry, string $property, string $cypher): array {
                    $carry[$property] = $cypher;

                    return $carry;
                }
            ))
            ->withParameter(
                $paramKey->toString(),
                $data
                    ->filter(static function(string $key) use ($keysToKeep): bool {
                        return $keysToKeep->contains($key);
                    })
                    ->put($meta->identity()->property(), $identity->value())
                    ->reduce(
                        [],
                        static function(array $carry, string $key, $value): array {
                            /** @psalm-suppress MixedAssignment */
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
        if ($this->variables->contains($name->toString())) {
            return $query;
        }

        if ($this->variables->size() > 0) {
            $query = $query->with(...unwrap($this->variables));
        }

        $this->variables = $this->variables->add($name->toString());

        return $query
            ->match($name->toString())
            ->withProperty(
                $meta->target(),
                $name
                    ->prepend('{')
                    ->append('_props}.')
                    ->append($meta->target())
                    ->toString(),
            )
            ->withParameter(
                $name->append('_props')->toString(),
                [
                    $meta->target() => $value,
                ]
            );
    }
}
