<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Entity\ChangesetComputer,
    IdentityInterface,
    Event\EntityAboutToBeRemoved,
    Event\EntityRemoved,
    Metadatas,
    Metadata\Relationship,
    Metadata\ValueObject
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    QueryInterface,
    Query
};
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Collection,
    StringPrimitive as Str,
    Set,
    MapInterface
};

class RemovePersister implements PersisterInterface
{
    private $changeset;
    private $eventBus;
    private $metadatas;
    private $name;
    private $variables;

    public function __construct(
        ChangesetComputer $changeset,
        EventBusInterface $eventBus,
        Metadatas $metadatas
    ) {
        $this->changeset = $changeset;
        $this->eventBus = $eventBus;
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
                $this->eventBus->dispatch(
                    new EntityAboutToBeRemoved($identity, $object)
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
            $this->eventBus->dispatch(
                new EntityRemoved($identity, $object)
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
        $partitions = $entities->partition(function(
            IdentityInterface $identity,
            $entity
        ) {
            $meta = $this->metadatas->get(get_class($entity));

            return $meta instanceof Relationship;
        });

        $partitions
            ->get(true)
            ->foreach(function(
                IdentityInterface $identity,
                $entity
            ) use (
                &$query
            ) {
                $query = $this->matchRelationship($identity, $entity, $query);
            });
        $partitions
            ->get(false)
            ->foreach(function(
                IdentityInterface $identity,
                $entity
            ) use (
                &$query
            ) {
                $query = $this->matchAggregate($identity, $entity, $query);
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
     * @param IdentityInterface $identity
     * @param $object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function matchRelationship(
        IdentityInterface $identity,
        $entity,
        Query $query
    ): Query {
        $meta = $this->metadatas->get(get_class($entity));
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
     * @param IdentityInterface $identity
     * @param object $entity
     * @param Query  $query
     *
     * @return Query
     */
    private function matchAggregate(
        IdentityInterface $identity,
        $entity,
        Query $query
    ): Query {
        $meta = $this->metadatas->get(get_class($entity));
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
}
