<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Persister;

use Innmind\Neo4j\ONM\{
    PersisterInterface,
    Entity\Container,
    Entity\ChangesetComputer,
    Entity\DataExtractor,
    IdentityInterface,
    Events,
    Event\UpdateEvent,
    Metadata\Aggregate,
    Metadata\ValueObject,
    Metadata\Relationship,
    Metadatas
};
use Innmind\Neo4j\DBAL\{
    ConnectionInterface,
    QueryInterface,
    Query
};
use Innmind\Immutable\{
    CollectionInterface,
    StringPrimitive as Str,
    Map,
    MapInterface
};
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdatePersister implements PersisterInterface
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
        $changesets = new Map(
            IdentityInterface::class,
            CollectionInterface::class
        );

        $entities = $container
            ->state(Container::STATE_MANAGED)
            ->foreach(function(
                IdentityInterface $identity,
                $entity
            ) use (
                &$changesets
            ) {
                $data = $this->extractor->extract($entity);
                $changeset = $this->changeset->compute($identity, $data);

                if ($changeset->count() > 0) {
                    $changesets = $changesets->put(
                        $identity,
                        $changeset
                    );
                }
            });

        if ($changesets->size() === 0) {
            return;
        }

        $changesets->foreach(function(
            IdentityInterface $identity,
            CollectionInterface $changeset
        ) use (
            $entities
        ) {
            $this->dispatcher->dispatch(
                Events::PRE_UPDATE,
                new UpdateEvent(
                    $identity,
                    $entities->get($identity),
                    $changeset
                )
            );
        });

        $connection->execute($this->queryFor($changesets, $entities));

        $changesets->foreach(function(
            IdentityInterface $identity,
            CollectionInterface $changeset
        ) use (
            $entities
        ) {
            $entity = $entities->get($identity);
            $this->changeset->use(
                $identity,
                $this->extractor->extract($entity)
            );
            $this->dispatcher->dispatch(
                Events::POST_UPDATE,
                new UpdateEvent(
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
     * @param MapInterface<IdentityInterface, CollectionInterface> $changesets
     * @param MapInterface<IdentityInterface, object> $entities
     *
     * @return QueryInterface
     */
    private function queryFor(
        MapInterface $changesets,
        MapInterface $entities
    ): QueryInterface {
        $query = new Query;
        $this->variables = new Map(Str::class, CollectionInterface::class);

        $changesets->foreach(function(
            IdentityInterface $identity,
            CollectionInterface $changeset
        ) use (
            &$query,
            $entities
        ) {
            $entity = $entities->get($identity);
            $meta = $this->metadatas->get(get_class($entity));

            if ($meta instanceof Aggregate) {
                $query = $this->matchAggregate(
                    $identity,
                    $entity,
                    $meta,
                    $changeset,
                    $query
                );
            } else {
                $query = $this->matchRelationship(
                    $identity,
                    $entity,
                    $meta,
                    $changeset,
                    $query
                );
            }
        });
        $this
            ->variables
            ->foreach(function(
                Str $variable,
                CollectionInterface $changeset
            ) use (
                &$query
            ) {
                $query = $this->update($variable, $changeset, $query);
            });
        $this->variables = null;

        return $query;
    }

    /**
     * Add match clause to match all parts of the aggregate that needs to be updated
     *
     * @param IdentityInterface $identity
     * @param obkect $entity
     * @param Aggregate $meta
     * @param CollectionInterface $changeset
     * @param Query $query
     *
     * @return Query
     */
    private function matchAggregate(
        IdentityInterface $identity,
        $entity,
        Aggregate $meta,
        CollectionInterface $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(md5($identity->value()));
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

        $meta
            ->children()
            ->foreach(function(
                string $property,
                ValueObject $child
            ) use (
                &$query,
                $changeset,
                $name
            ) {
                if (!$changeset->hasKey($property)) {
                    return;
                }

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

                if ($changeset->hasKey($child->relationship()->childProperty())) {
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

                $query = $query
                    ->match((string) $name)
                    ->linkedTo(
                        $childName ? (string) $childName : null,
                        $child->labels()->toPrimitive()
                    )
                    ->through(
                        (string) $child->relationship()->type(),
                        (string) $relName
                    );
            });

        return $query;
    }

    /**
     * Add the match clause for a relationship
     *
     * @param IdentityInterface $identity
     * @param object $entity
     * @param Relationship $meta
     * @param CollectionInterface $changeset
     * @param Query $query
     *
     * @return Query
     */
    private function matchRelationship(
        IdentityInterface $identity,
        $entity,
        Relationship $meta,
        CollectionInterface $changeset,
        Query $query
    ): Query {
        $name = $this->name->sprintf(md5($identity->value()));
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
     * @param CollectionInterface $changeset
     * @param MapInterface<string, Property> $properties
     *
     * @return CollectionInterface
     */
    private function buildProperties(
        CollectionInterface $changeset,
        MapInterface $properties
    ): CollectionInterface {
        return $changeset->filter(function(
            $data,
            string $property
        ) use (
            $properties
        ) {
            return $properties->contains($property);
        });
    }

    /**
     * Add a clause to set all properties to be updated on the wished variable
     *
     * @param Str $variable
     * @param CollectionInterface $changeset
     * @param Query $query
     *
     * @return Query
     */
    private function update(
        Str $variable,
        CollectionInterface $changeset,
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
                $changeset->toPrimitive()
            );
    }
}
