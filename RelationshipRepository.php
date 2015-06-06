<?php

namespace Innmind\Neo4j\ONM;

class RelationshipRepository extends Repository
{
    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $skip = null)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->addExpr(
                $qb
                    ->expr()
                    ->matchNode()
                    ->relatedTo(
                        $qb
                            ->expr()
                            ->matchRelationship(
                                'r',
                                $this->entityClass,
                                $criteria
                            )
                    )
            )
            ->toReturn('r');

        if ($orderBy !== null) {
            $qb->orderBy(
                sprintf('r.%s', $orderBy[0]),
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
