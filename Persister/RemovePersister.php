<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Entity\ChangesetComputer,
    IdentityInterface,
    Events,
    Event\RemoveEvent,
    Metadatas,
    Metadata\Relationship,
    Metadata\ValueObject
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    QueryInterface,
    Query
};
use Innmind\Immutable\{
    Collection,
    StringPrimitive as Str,
    Set,
    MapInterface
};
use Innmind\Reflection\ReflectionObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RemovePersister implements PersisterInterface
{
    private $changeset;
    private $dispatcher;
    private $metadatas;
    private $name;

    public function __construct(
        ChangesetComputer $changeset,
        EventDispatcherInterface $dispatcher,
        Metadatas $metadatas
    ) {
        $this->changeset = $changeset;
        $this->dispatcher = $dispatcher;
        $this->metadatas = $metadatas;
        $this->name = new Str('e%s');
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ConnectionInterface $connection, Container $container)
    {
        $entities = $container
            ->state(Container::STATE_TO_BE_REMOVED)
            ->foreach(function(IdentityInterface $identity, $object) {
                $this->dispatcher->dispatch(
                    Events::PRE_REMOVE,
                    new RemoveEvent($identity, $object)
                );
            });

        if ($entities->size() === 0) {
            return;
        }

        $connection->execute($this->queryFor($entities));

        $entities->foreach(function(
            IdentityInterface $identity,
            $object
        ) use (
            $container
        ) {
            $container->push($identity, $object, Container::STATE_REMOVED);
            $this->changeset->use($identity, new Collection([])); //in case the identity is reused later on
            $this->dispatcher->dispatch(
                Events::POST_REMOVE,
                new RemoveEvent($identity, $object)
            );
        });
    }

    /**
     * Build the query to delete all entities at once
     *
     * @param MapInterface<IdentityInterface, object> $entities
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

                return $meta instanceof Relationship;
            });

        $partitions
            ->get(0)
            ->foreach(function($entity) use (&$query) {
                $query = $this->matchRelationship($entity, $query);
            });
        $partitions
            ->get(1)
            ->foreach(function($entity) use (&$query) {
                $query = $this->matchAggregate($entity, $query);
            });
        $this
            ->variables
            ->foreach(function(string $variable) use (&$query) {
                $query = $query->delete($variable);
            });
        $this->variables = null;

        return $query;
    }

    /**
     * Add clause to match the relationship we want to delete
     *
     * @param $object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function matchRelationship($entity, Query $query): Query
    {
        $meta = $this->metadatas->get(get_class($entity));
        $identity = $this->extractIdentity($entity);
        $name = $this->name->sprintf(md5($identity->value()));
        $this->variables = $this->variables->add((string) $name);

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
     * Add clause to match the node we want to delete and all of its children
     *
     * @param object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function matchAggregate($entity, Query $query): Query
    {
        $meta = $this->metadatas->get(get_class($entity));
        $identity = $this->extractIdentity($entity);
        $name = $this->name->sprintf(md5($identity->value()));
        $this->variables = $this->variables->add((string) $name);

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

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$query,
                $name
            ) {
                $query = $query
                    ->match((string) $name)
                    ->linkedTo(
                        $childName = (string) $name
                            ->append('_')
                            ->append($child->relationship()->property())
                            ->append('_')
                            ->append($child->relationship()->childProperty()),
                        $child->labels()->toPrimitive()
                    )
                    ->through(
                        (string) $child->relationship()->type(),
                        $relName = (string) $name
                            ->append('_')
                            ->append($child->relationship()->property())
                    );
                $this->variables = $this->variables
                    ->add($childName)
                    ->add($relName);
            });

        return $query;
    }

    /**
     * Extract the identity of the given entity
     *
     * @param object $entity
     *
     * @return IdentityInterface
     */
    private function extractIdentity($entity): IdentityInterface
    {
        $id = $this
            ->metadatas
            ->get(get_class($entity))
            ->identity()
            ->property();

        return (new ReflectionObject($entity))
            ->extract([$id])
            ->get($id);
    }
}
