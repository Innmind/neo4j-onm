<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Entity\ChangesetComputer,
    Entity\DataExtractor,
    Events,
    Event\PersistEvent,
    IdentityInterface,
    Metadata\Aggregate,
    Metadata\Property,
    Metadata\ValueObject,
    Metadata\RelationshipEdge,
    Metadatas
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    QueryInterface,
    Query,
    Clause\Expression\Relationship as DBALRelationship
};
use Innmind\Immutable\{
    Collection,
    CollectionInterface,
    StringPrimitive as Str,
    MapInterface,
    Set
};
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InsertPersister implements PersisterInterface
{
    private $changeset;
    private $dispatcher;
    private $extractor;
    private $metadatas;
    private $name;
    private $variables;

    public function __construct(
        ChangesetComputer $changeset,
        EventDispatcherInterface $dispatcher,
        DataExtractor $extractor,
        Metadatas $metadatas
    ) {
        $this->changeset = $changeset;
        $this->dispatcher = $dispatcher;
        $this->extractor = $extractor;
        $this->metadatas = $metadatas;
        $this->name = new Str('e%s');
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ConnectionInterface $connection, Container $container)
    {
        $entities = $container->state(Container::STATE_NEW);

        $entities->foreach(function(IdentityInterface $identity, $entity) {
            $this->dispatcher->dispatch(
                Events::PRE_PERSIST,
                new PersistEvent($identity, $entity)
            );
        });

        $connection->execute($this->queryFor($entities));

        $entities->foreach(function(
            IdentityInterface $identity,
            $entity
        ) use (
            $container
        ) {
            $container->push($identity, $entity, Container::STATE_MANAGED);
            $this->changeset->use(
                $identity,
                $this->extractor->extract($entity)
            );
            $this->dispatcher->dispatch(
                Events::POST_PERSIST,
                new PersistEvent($identity, $entity)
            );
        });
    }

    /**
     * Build the whole cypher query to insert at once all new nodes and relationships
     *
     * @param MapInterface $entities
     *
     * @return QueryInterface
     */
    private function queryFor(MapInterface $entities): QueryInterface
    {
        $query = new Query;
        $this->variables = new Set('string');

        $partitions = $entities
            ->values()
            ->partition(function($entity) {
                $meta = $this->metadatas->get(get_class($entity));

                return $meta instanceof Aggregate;
            });
        $partitions
            ->get(0)
            ->foreach(function($entity) use (&$query) {
                $query = $this->createAggregate($entity, $query);
            });
        $partitions
            ->get(1)
            ->foreach(function($entity) use (&$query) {
                $query = $this->createRelationship($entity, $query);
            });
        $this->variables = null;

        return $query;
    }

    /**
     * Add the cypher clause to create the node corresponding to the root of the aggregate
     *
     * @param object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function createAggregate($entity, Query $query): Query
    {
        $meta = $this->metadatas->get(get_class($entity));
        $data = $this->extractor->extract($entity);
        $identity = $data->get($meta->identity()->property());
        $varName = $this->name->sprintf(md5($identity));

        $query = $query->create(
            (string) $varName,
            $meta->labels()->toPrimitive()
        );
        $paramKey = $varName->append('_props');
        $properties = $this->buildProperties(
            $meta->properties(),
            $paramKey
        );

        $query = $query
            ->withProperty(
                $meta->identity()->property(),
                (string) $paramKey
                    ->prepend('{')
                    ->append('}.')
                    ->append($meta->identity()->property())
            )
            ->withProperties($properties->toPrimitive())
            ->withParameters([
                (string) $paramKey => $data
                    ->keyIntersect($properties)
                    ->set($meta->identity()->property(), $identity)
                    ->toPrimitive()
            ]);

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$query,
                $varName,
                $data
            ) {
                $query = $this->createAggregateChild(
                    $child,
                    $varName,
                    $data->get($property),
                    $query
                );
            });
        $this->variables = $this->variables->add((string) $varName);

        return $query;
    }

    /**
     * Add the cypher clause to build the relationship and the node corresponding
     * to a child of the aggregate
     *
     * @param ValueObject $meta
     * @param Str $nodeName
     * @param CollectionInterface $data
     * @param Query $query
     *
     * @return Query
     */
    private function createAggregateChild(
        ValueObject $meta,
        Str $nodeName,
        CollectionInterface $data,
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
            ->withProperties($endNodeProperties->toPrimitive())
            ->withParameters([
                (string) $endNodeParamKey => $data
                    ->get(
                        $meta->relationship()->childProperty()
                    )
                    ->toPrimitive()
            ])
            ->through(
                (string) $meta->relationship()->type(),
                (string) $relationshipName,
                DBALRelationship::LEFT
            )
            ->withProperties($relationshipProperties->toPrimitive())
            ->withParameters([
                (string) $relationshipParamKey => $data
                    ->unset(
                        $meta->relationship()->childProperty()
                    )
                    ->toPrimitive()
            ]);
    }

    /**
     * Build the collection of properties to be injected in the query
     *
     * @param MapInterface $properties
     * @param Str $name
     *
     * @return CollectionInterface
     */
    private function buildProperties(
        MapInterface $properties,
        Str $name
    ): CollectionInterface {
        $data = new Collection([]);
        $name = $name->prepend('{')->append('}.');

        $properties->foreach(function(string $property) use (&$data, $name) {
            $data = $data->set(
                $property,
                (string) $name->append($property)
            );
        });

        return $data;
    }

    /**
     * Add the clause to create a relationship between nodes
     *
     * @param object $entity
     * @param Query $query
     *
     * @return Query
     */
    private function createRelationship($entity, Query $query): Query
    {
        $meta = $this->metadatas->get(get_class($entity));
        $data = $this->extractor->extract($entity);
        $identity = $data->get($meta->identity()->property());
        $start = $data->get($meta->startNode()->property());
        $end = $data->get($meta->endNode()->property());
        $varName = $this->name->sprintf(md5($identity));
        $startName = $this->name->sprintf(md5($start));
        $endName = $this->name->sprintf(md5($end));

        $properties = $this->buildProperties($meta->properties(), $varName);
        $paramKey = $varName->append('_props');

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
            ->withProperties($properties->toPrimitive())
            ->withParameters([
                (string) $paramKey => $data
                    ->keyIntersect($properties)
                    ->set($meta->identity()->property(), $identity)
                    ->toPrimitive()
            ]);
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
