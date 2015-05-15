<?php

namespace Innmind\Neo4j\ONM;

abstract class Repository implements RepositoryInterface
{
    protected $em;
    protected $entityClass;

    public function __construct(EntityManagerInterface $em, $entityClass)
    {
        $this->em = $em;
        $this->entityClass = (string) $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->em->getUnitOfWork()->clear($this->entityClass);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->em->getUnitOfWork()->find($this->entityClass, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->findBy($criteria, $orderBy, 1);
    }

    /**
     * Magic method to find one or more nodes based on one criteria
     *
     * @param string $method
     * @param array $arguments
     *
     * @return array|object
     */
    public function __call($method, array $arguments)
    {
        switch (true) {
            case (strpos($method, 'findBy') === 0):
                $by = substr($method, 6);
                $method = 'findBy';
                break;
            case (strpos($method, 'findOneBy') === 0):
                $by = substr($method, 9);
                $method = 'findOneBy';
                break;
            default:
                throw new \BadMethodCallException(
                    sprintf(
                        'Undefined method "%s". It must start by either findBy or findOneBy',
                        $method
                    )
                );
        }

        return call_user_func_array([$this, $method], $arguments);
    }

    /**
     * Return the entity manager
     *
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }
}
