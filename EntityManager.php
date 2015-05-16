<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\DBAL\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityManager implements EntityManagerInterface
{
    protected $conn;
    protected $config;
    protected $dispatcher;
    protected $uow;
    protected $repoFactory;

    public function __construct(ConnectionInterface $conn, Configuration $config, EventDispatcherInterface $dispatcher)
    {
        $this->conn = $conn;
        $this->config = $config;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->conn->openTransaction();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->conn->commit();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->conn->rollback();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return $this->uow;
    }

    /**
     * {@inheritdoc}
     */
    public function find($class, $id)
    {
        return $this->uow->find($class, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $this->uow->persist($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity)
    {
        $this->uow->remove($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($alias = null)
    {
        $this->uow->clear($alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function detach($entity)
    {
        $this->uow->detach($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($entity)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->uow->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($alias)
    {
        return $this->repoFactory->make($alias, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($entity)
    {
        return $this->uow->isManaged($entity);
    }
}
