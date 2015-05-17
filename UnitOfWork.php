<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Neo4j\ONM\Exception\UnrecognizedEntityException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    protected $states;
    protected $entities;
    protected $scheduledForUpdate;
    protected $scheduledForInsert;
    protected $scheduledForDelete;

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

        $this->states = [
            self::STATE_MANAGED => new \SplObjectStorage,
            self::STATE_NEW => new \SplObjectStorage,
            self::STATE_DETACHED => new \SplObjectStorage,
            self::STATE_REMOVED => new \SplObjectStorage,
        ];
        $this->scheduledForInsert = new \SplObjectStorage;
        $this->scheduledForUpdate = new \SplObjectStorage;
        $this->scheduledForDelete = new \SplObjectStorage;
        $this->entities = new \SplObjectStorage;
    }

    /**
     * Find an entity by its id
     *
     * @param string $class
     * @param mixed $id
     *
     * @return object
     */
    public function find($class, $id)
    {

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
     * @return array
     */
    public function findBy($class, array $criteria, array $orderBy = null, $limit = null, $skip = null)
    {

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

        $this->states[self::STATE_DETACHED]->detach($entity);
        $this->states[self::STATE_REMOVED]->detach($entity);

        if (!$this->entities->contains($entity)) {
            $this->states[self::STATE_NEW]->attach($entity);
            $this->scheduledForInsert->attach($entity);
        } else {
            $this->states[self::STATE_MANAGED]->attach($entity);
            $this->scheduledForUpdate->attach($entity);
        }

        $this->entities->attach($entity);

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
        $this->scheduledForUpdate->detach($entity);
        $this->scheduledForDelete->attach($entity);

        if ($this->states[self::STATE_NEW]->contains($entity)) {
            $this->states[self::STATE_NEW]->detach($entity);
            $this->states[self::STATE_REMOVED]->attach($entity);
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
        $this->states[self::STATE_NEW]->detach($entity);
        $this->states[self::STATE_MANAGED]->detach($entity);
        $this->states[self::STATE_REMOVED]->detach($entity);
        $this->states[self::STATE_DETACHED]->attach($entity);

        $this->scheduledForInsert->detach($entity);
        $this->scheduledForUpdate->detach($entity);
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
        return $this->states[self::STATE_MANAGED]->contains($entity) ||
            $this->states[self::STATE_NEW]->contains($entity);
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
        foreach ($this->states as $state => $entities) {
            if ($entities->contains($entity)) {
                return $state;
            }
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
     * Check if the entity is scheduled for update
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForUpdate($entity)
    {
        return $this->scheduledForUpdate->contains($entity);
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
     * Check if the entity is known in the identity map
     *
     * @throws UnrecognizedEntityException If the entity class is not in the identity map
     *
     * @return void
     */
    protected function checkKnown($entity)
    {
        if (!$this->identityMap->has(get_class($entity))) {
            throw new UnrecognizedEntityException(sprintf(
                'The class "%s" is not known as an entity by this manager',
                get_class($entity)
            ));
        }
    }
}
