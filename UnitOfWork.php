<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Generators;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\Metadata;
use Innmind\Neo4j\ONM\Mapping\Types;
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Neo4j\ONM\Exception\UnrecognizedEntityException;
use Innmind\Neo4j\ONM\Exception\EntityNotFoundException;
use Innmind\Neo4j\ONM\Exception\UnknwonPropertyException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class UnitOfWork
{
    const STATE_MANAGED = 1;
    const STATE_NEW = 2;
    const STATE_DETACHED = 3;
    const STATE_REMOVED = 4;

    protected $conn;
    protected $identityMap;
    protected $metadataRegistry;
    protected $dispatcher;
    protected $hydrator;
    protected $accessor;
    protected $entities;
    protected $scheduledForInsert;
    protected $scheduledForDelete;
    protected $entitySilo;
    protected $persistSequence;

    public function __construct(
        ConnectionInterface $conn,
        IdentityMap $map,
        MetadataRegistry $registry,
        EventDispatcherInterface $dispatcher
    ) {
        $this->conn = $conn;
        $this->identityMap = $map;
        $this->metadataRegistry = $registry;
        $this->dispatcher = $dispatcher;

        $this->scheduledForInsert = new \SplObjectStorage;
        $this->scheduledForDelete = new \SplObjectStorage;
        $this->entities = new \SplObjectStorage;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->entitySilo = new EntitySilo;
        $this->persistSequence = new \SplObjectStorage;

        $this->hydrator = new Hydrator($this, $this->entitySilo, $this->accessor);
    }

    /**
     * Return the identity map
     *
     * This method should NEVER be used by the end developer
     *
     * @return IdentityMap
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * Return the metadata registry
     *
     * This method should NEVER be used by the end developer
     *
     * @return MetadataRegistry
     */
    public function getMetadataRegistry()
    {
        return $this->metadataRegistry;
    }

    /**
     * Find an entity by its id
     *
     * @param string $class
     * @param mixed $id
     *
     * @throws InvalidArgumentException If the class name is not recognized
     * @throws EntityNotFoundException If the entity is not found
     *
     * @return object
     */
    public function find($class, $id)
    {
        if (!$this->identityMap->has($class)) {
            throw new \InvalidArgumentException(sprintf(
                'The entity "%s" is not handled by this manager',
                $class
            ));
        }

        $class = $this->identityMap->getClass($class);

        if ($this->entitySilo->has($class, $id)) {
            return $this->entitySilo->get($class, $id);
        }

        $metadata = $this->metadataRegistry->getMetadata($class);

        if ($metadata instanceof NodeMetadata) {
            $format = sprintf(
                '(e:%s)',
                $class
            );
        } else {
            $format = sprintf(
                '()-[e:%s]-()',
                $class
            );
        }

        $query = new Query(sprintf(
            'MATCH %s WHERE e.%s = {props}.id RETURN e;',
            $format,
            $metadata->getId()->getProperty()
        ));
        $query
            ->addVariable('e', $class)
            ->addParameters(
                'props',
                ['id' => $id],
                ['id' => sprintf('e.%s', $metadata->getId()->getProperty())]
            );

        $results = $this->execute($query);

        if ($results->count() === 0) {
            throw new EntityNotFoundException(sprintf(
                'The entity "%s" with the id "%s" not found',
                $class,
                $id
            ));
        }

        return $results->first();
    }

    /**
     * Find entities by the search criteria
     *
     * @param string $class
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $skip
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy($class, array $criteria, array $orderBy = null, $limit = null, $skip = null)
    {
        if (empty($criteria)) {
            throw new \LogicException(
                'You can\'t search for nodes without specifying any criteria'
            );
        }

        $class = $this->identityMap->getClass($class);
        $metadata = $this->metadataRegistry->getMetadata($class);

        foreach ($criteria as $key => $value) {
            if (!$metadata->hasProperty($key)) {
                throw new UnknwonPropertyException(sprintf(
                    'Unknown property "%s" for the entity "%s"',
                    $key,
                    $class
                ));
            }
        }

        $qb = new QueryBuilder;

        if ($metadata instanceof NodeMetadata) {
            $qb->matchNode('e', $class, $criteria);
        } else {
            $qb->addExpr(
                $qb
                    ->expr()
                    ->matchNode()
                    ->relatedTo(
                        $qb
                            ->expr()
                            ->matchRelationship('e', $class, $criteria)
                    )
            );
        }

        $qb->toReturn('e');

        if ($orderBy !== null) {
            if (!$metadata->hasProperty($orderBy[0])) {
                throw new UnknwonPropertyException(sprintf(
                    'Unknown property "%s" for the entity "%s"',
                    $key,
                    $class
                ));
            }

            $qb->orderBy(
                sprintf('e.%s', $orderBy[0]),
                $orderBy[1]
            );
        }

        if ($skip !== null) {
            $qb->skip($skip);
        }

        if ($limit !== null) {
            $qb->limit($limit);
        }

        return $this->execute($qb->getQuery());
    }

    /**
     * Execute the given query
     *
     * @param Query $query
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function execute(Query $query)
    {
        $cypher = $this->buildQuery($query);
        $params = $this->cleanParameters($query);

        $results = $this->conn->execute($cypher, $params);

        $entities = $this->hydrator->hydrate($results, $query);

        $entities->forAll(function($idx, $entity) {
            $this->entities->attach($entity, self::STATE_MANAGED);

            return true;
        });

        return $entities;
    }

    /**
     * Plan an entity to be persisted at next commit
     *
     * @param object $entity
     *
     * @return UnitOfWork self
     */
    public function persist($entity)
    {
        $this->checkKnown($entity);

        if ($this->persistSequence->contains($entity)) {
            return $this;
        }

        $this->persistSequence->attach($entity);

        if (!$this->entities->contains($entity)) {
            $this->entities->attach($entity, self::STATE_NEW);
            $this->scheduledForInsert->attach($entity);
            $id = $this->generateId($entity);
            $class = $this->getClass($entity);
            $this->entitySilo->add(
                $entity,
                $class,
                $id,
                [
                    'properties' => $this->getEntityData(
                        $entity,
                        $this->metadataRegistry->getMetadata($class)
                    ),
                ]
            );
        } else {
            $this->entities->attach($entity, self::STATE_MANAGED);
        }

        $this->cascadePersist($entity);

        $this->persistSequence->detach($entity);

        return $this;
    }

    /**
     * Plan an entity to be removed at next commit
     *
     * @param object $entity
     *
     * @return UnitOfWork self
     */
    public function remove($entity)
    {
        $this->checkKnown($entity);

        $this->scheduledForInsert->detach($entity);
        $this->scheduledForDelete->attach($entity);

        if ($this->entities[$entity] === self::STATE_NEW) {
            $this->entities[$entity] = self::STATE_REMOVED;
            $this->scheduledForDelete->detach($entity);
        }

        return $this;
    }

    /**
     * Detach all entities of the specified class
     *
     * @param string $class
     *
     * @return UnitOfWork self
     */
    public function clear($class = null)
    {
        if ($class !== null) {
            $class = $this->identityMap->getClass((string) $class);
        }

        foreach ($this->entities as $entity) {
            if ($class !== null && !($entity instanceof $class)) {
                continue;
            }

            $this->detach($entity);
        }

        return $this;
    }

    /**
     * Detach the entity
     *
     * @param object $entity
     *
     * @return UnitOfWork self
     */
    public function detach($entity)
    {
        if ($this->entities->contains($entity)) {
            $this->entities[$entity] = self::STATE_DETACHED;
        }

        $this->scheduledForInsert->detach($entity);
        $this->scheduledForDelete->detach($entity);

        return $this;
    }

    /**
     * Commit all the modifications to the database
     *
     * @return UnitOfWork self
     */
    public function commit()
    {
        $toInsert = $this->computeInsertQuery();
        $toUpdate = $this->computeUpdateQuery();
        $toDelete = $this->computeDeleteQuery();

        if ($toInsert->hasVariables()) {
            $this->execute($toInsert);

            foreach ($this->scheduledForInsert as $entity) {
                $this->entities[$entity] = self::STATE_MANAGED;
            }

            $this->scheduledForInsert = new \SplObjectStorage;
        }

        if ($toUpdate->hasVariables()) {
            $this->execute($toUpdate);
        }

        if ($toDelete->hasVariables()) {
            $this->execute($toDelete);

            foreach ($this->scheduledForDelete as $entity) {
                $this->entities[$entity] = self::STATE_REMOVED;
            }

            $this->scheduledForDelete = new \SplObjectStorage;
        }

        return $this;
    }

    /**
     * Check if an entity is managed
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isManaged($entity)
    {
        if (!$this->entities->contains($entity)) {
            return false;
        }

        return in_array(
            $this->entities[$entity],
            [self::STATE_NEW, self::STATE_MANAGED],
            true
        );
    }

    /**
     * Return the state for the given entity
     *
     * @param object $entity
     *
     * @return int
     */
    public function getEntityState($entity)
    {
        if ($this->entities->contains($entity)) {
            return $this->entities[$entity];
        }

        return self::STATE_DETACHED;
    }

    /**
     * Check if the entity is scheduled for insertion
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForInsert($entity)
    {
        return $this->scheduledForInsert->contains($entity);
    }

    /**
     * Check if the entity is scheduled for removal
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForDelete($entity)
    {
        return $this->scheduledForDelete->contains($entity);
    }

    /**
     * Take the cypher query and replace all the alias by the real
     * labels and relationships types, as well as correct nodes id
     *
     * @param Query $query
     *
     * @return string
     */
    public function buildQuery(Query $query)
    {
        $variables = $query->getVariables();
        $cypher = $query->getCypher();

        foreach ($variables as $variable => $alias) {
            $class = $this->identityMap->getClass($alias);
            $metadata = $this->metadataRegistry->getMetadata($class);

            if ($metadata instanceof NodeMetadata) {
                $labels = implode(':', $metadata->getLabels());

                $search = sprintf(
                    '(%s:%s',
                    $variable,
                    $alias
                );
                $replace = sprintf(
                    '(%s:%s',
                    $variable,
                    $labels
                );
            } else {
                $search = sprintf(
                    '[%s:%s',
                    $variable,
                    $alias
                );
                $replace = sprintf(
                    '[%s:%s',
                    $variable,
                    $metadata->getType()
                );
            }

            $cypher = str_replace($search, $replace, $cypher);
        }

        return $cypher;
    }

    /**
     * Check if the entity is known in the identity map
     *
     * @throws UnrecognizedEntityException If the entity class is not in the identity map
     *
     * @return void
     */
    protected function checkKnown($entity)
    {
        if (!$this->identityMap->has($this->getClass($entity))) {
            throw new UnrecognizedEntityException(sprintf(
                'The class "%s" is not known as an entity by this manager',
                $this->getClass($entity)
            ));
        }
    }

    /**
     * Clean query parameters by converting via the types defined by the properties
     *
     * @param Query $query
     *
     * @return array
     */
    protected function cleanParameters(Query $query)
    {
        $params = $query->getParameters();
        $references = $query->getReferences();
        $variables = $query->getVariables();

        foreach ($params as $key => &$values) {
            if (!isset($references[$key])) {
                continue;
            }

            foreach ($values as $k => &$value) {
                if (!isset($references[$key][$k])) {
                    continue;
                }

                list($var, $prop) = explode('.', $references[$key][$k]);
                $class = $this->identityMap->getClass($variables[$var]);
                $metadata = $this->metadataRegistry->getMetadata($class);

                if (!$metadata->hasProperty($prop)) {
                    continue;
                }

                $prop = $metadata->getProperty($prop);

                $value = Types::getType($prop->getType())
                    ->convertToDatabaseValue($value, $prop);
            }
        }

        return $params;
    }

    /**
     * Return the class of an entity
     *
     * @param object $entity
     *
     * @return string
     */
    protected function getClass($entity)
    {
        return $this->entitySilo->getClass($entity) ?: get_class($entity);
    }

    /**
     * Generate an id for the given entity
     *
     * @param object $entity
     *
     * @return mixed
     */
    protected function generateId($entity)
    {
        $class = $this->getClass($entity);
        $metadata = $this->metadataRegistry->getMetadata($class);

        $id = Generators::getGenerator($metadata->getId()->getStrategy())
            ->generate($this, $entity);

        $this->accessor->setValue(
            $entity,
            $metadata->getId()->getProperty(),
            $id
        );

        return $id;
    }

    /**
     * Look at all the entities set in each managed entity and persist them
     *
     * @param object $entity
     *
     * @return void
     */
    protected function cascadePersist($entity)
    {
        $class = $this->getClass($entity);
        $metadata = $this->metadataRegistry->getMetadata($class);

        foreach ($metadata->getProperties() as $property) {
            if (!$metadata->isReference($property)) {
                continue;
            }

            $extracted = $this->accessor->getValue(
                $entity,
                $property->getName()
            );

            if (empty($extracted)) {
                continue;
            }

            if ($property->hasOption('collection') && $property->getOption('collection') === true) {
                foreach ($extracted as $subEntity) {
                    $this->persist($subEntity);
                }
            } else {
                $this->persist($extracted);
            }
        }
    }

    /**
     * Create all queries to insert al new entities
     *
     * @return Query
     */
    protected function computeInsertQuery()
    {
        $nodes = new \SplObjectStorage;
        $rels = new \SplObjectStorage;
        $qb = new QueryBuilder;
        $nodeIndex = 0;
        $relIndex = 0;

        foreach ($this->scheduledForInsert as $entity) {
            $class = $this->getClass($entity);
            $metadata = $this->metadataRegistry->getMetadata($class);

            if ($metadata instanceof NodeMetadata) {
                $nodes->attach($entity, $nodeIndex);
                $nodeIndex++;
            } else {
                $rels->attach($entity, $relIndex);
                $relIndex++;
            }
        }

        $idx = 0;

        foreach ($nodes as $node) {
            $class = $this->getClass($node);
            $metadata = $this->metadataRegistry->getMetadata($class);
            $data = $this->getEntityData($node, $metadata);

            $qb->create('n' . (string) $idx, $class, $data);

            $idx++;
        }

        $matchNodeIdx = 0;
        $idx = 0;

        foreach ($rels as $rel) {
            $class = $this->getClass($rel);
            $metadata = $this->metadataRegistry->getMetadata($class);

            $startNode = $this->accessor->getValue(
                $rel,
                $metadata->getStartNode()
            );
            $endNode = $this->accessor->getValue(
                $rel,
                $metadata->getEndNode()
            );

            if (!$nodes->contains($startNode)) {
                $startNodeClass = $this->getClass($startNode);
                $startNodeMeta = $this->metadataRegistry->getMetadata($startNodeClass);
                $startNodeIdProp = $startNodeMeta->getId()->getProperty();
                $startNodeId = $this->accessor->getValue(
                    $startNode,
                    $startNodeIdProp
                );
                $startVar = 'mn' . (string) $matchNodeIdx;
                $matchNodeIdx++;

                $qb->matchNode(
                    $startVar,
                    $startNodeClass,
                    [
                        $startNodeIdProp => $startNodeId
                    ]
                );
            } else {
                $startVar = 'n' . (string) $nodes[$startNode];
            }

            if (!$nodes->contains($endNode)) {
                $endNodeClass = $this->getClass($endNode);
                $endNodeMeta = $this->metadataRegistry->getMetadata($endNodeClass);
                $endNodeIdProp = $endNodeMeta->getId()->getProperty();
                $endNodeId = $this->accessor->getValue(
                    $endNode,
                    $endNodeIdProp
                );
                $endVar = 'mn' . (string) $matchNodeIdx;
                $matchNodeIdx++;

                $qb->matchNode(
                    $endVar,
                    $endNodeClass,
                    [
                        $endNodeIdProp => $endNodeId
                    ]
                );
            } else {
                $endVar = 'n' . (string) $nodes[$endNode];
            }

            $data = $this->getEntityData($rel, $metadata);

            $qb->createRelationship(
                $startVar,
                $endVar,
                'r' . (string) $idx,
                $metadata->getClass(),
                $data
            );

            $idx++;
        }

        return $qb->getQuery();
    }

    /**
     * Compute the query to update all the entities
     *
     * @return Query
     */
    protected function computeUpdateQuery()
    {
        $qb = new QueryBuilder;
        $toUpdate = [];
        $idx = 0;

        foreach ($this->entities as $entity) {
            if ($this->entities[$entity] !== self::STATE_MANAGED) {
                continue;
            }

            $class = $this->getClass($entity);
            $metadata = $this->metadataRegistry->getMetadata($class);
            $data = $this->computeChangeset($entity, $metadata);

            if (empty($data)) {
                continue;
            }

            $idProp = $metadata->getId()->getProperty();
            $id = $this->accessor->getValue(
                $entity,
                $idProp
            );
            $var = 'e' . (string) $idx;

            if ($metadata instanceof NodeMetadata) {
                $qb->matchNode($var, $class, [$idProp => $id]);
            } else {
                $qb->addExpr(
                    $qb
                        ->expr()
                        ->matchNode()
                        ->relatedTo(
                            $qb
                                ->expr()
                                ->matchRelationship(
                                    $var,
                                    $class,
                                    [$idProp => $id]
                                )
                        )
                );
            }

            $toUpdate[$var] = $data;
            $idx++;
        }

        foreach ($toUpdate as $var => $value) {
            $qb->update($var, $value);
        }

        return $qb->getQuery();
    }

    /**
     * Compute the query to delete all entities wished to be removed
     *
     * @return Query
     */
    protected function computeDeleteQuery()
    {
        $qb = new QueryBuilder;
        $variables = [];
        $idx = 0;

        foreach ($this->scheduledForDelete as $entity) {
            $class = $this->getClass($entity);
            $metadata = $this->metadataRegistry->getMetadata($class);
            $var = 'e' . (string) $idx;
            $idProp = $metadata->getId()->getProperty();
            $id = $this->accessor->getValue(
                $entity,
                $idProp
            );

            if ($metadata instanceof NodeMetadata) {
                $qb->matchNode($var, $class, [$idProp => $id]);
            } else {
                $qb->addExpr(
                    $qb
                        ->expr()
                        ->matchNode()
                        ->relatedTo(
                            $qb
                                ->expr()
                                ->matchRelationship(
                                    $var,
                                    $class,
                                    [$idProp => $id]
                                )
                        )
                );
            }

            $variables[] = $var;

            $idx++;
        }

        foreach ($variables as $var) {
            $qb->delete($var);
        }

        return $qb->getQuery();
    }

    /**
     * Extract entity data
     *
     * @param object $entity
     * @param Metadata $metadata
     *
     * @return array
     */
    protected function getEntityData($entity, Metadata $metadata)
    {
        $data = [];

        foreach ($metadata->getProperties() as $property) {
            if ($metadata->isReference($property)) {
                continue;
            }

            $data[$property->getName()] = $this->accessor->getValue(
                $entity,
                $property->getName()
            );
        }

        return $data;
    }

    /**
     * Try to find what changed in an entity between first retrieval and now
     *
     * @param object $entity
     * @param Metadata $metadata
     *
     * @return array
     */
    protected function computeChangeset($entity, Metadata $metadata)
    {
        $orig = $this->entitySilo->getInfo($entity)['properties'];
        $data = $this->getEntityData($entity, $metadata);
        $changeset = [];

        foreach ($data as $key => $value) {
            if (
                !array_key_exists($key, $orig) ||
                $value !== $orig[$key]
            ) {
                $changeset[$key] = $value;
            }
        }

        $id = $metadata->getId()->getProperty();

        if (isset($changeset[$id])) {
            throw new \LogicException(sprintf(
                'You can\'t change the id for "%s"',
                $metadata->getClass()
            ));
        }

        return $changeset;
    }
}
