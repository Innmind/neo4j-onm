<?php

namespace Innmind\Neo4j\ONM;

class NodeRepository extends Repository
{
    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $skip = null)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->matchNode('n', $this->entityClass, $criteria)
            ->toReturn('n');

        if ($orderBy !== null) {
            $qb->orderBy(
                sprintf('n.%s', $orderBy[0]),
                $orderBy[1]
            );
        }

        if ($skip !== null) {
            $qb->skip((int) $skip);
        }

        if ($limit !== null) {
            $qb->limit((int) $limit);
        }

        return $this->em->getUnitOfWork()->execute($qb->getQuery());
    }
}
