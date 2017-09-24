<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Repository;

use Innmind\Neo4j\ONM\{
    Repository as RepositoryInterface,
    UnitOfWork,
    Identity,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Metadata\Entity,
    Entity\Container,
    Exception\EntityNotFoundException
};
use Innmind\Immutable\{
    SetInterface,
    Set
};
use Innmind\Specification\SpecificationInterface;

class Repository implements RepositoryInterface
{
    private $unitOfWork;
    private $matchTranslator;
    private $specificationTranslator;
    private $metadata;
    private $allowedStates;

    public function __construct(
        UnitOfWork $unitOfWork,
        MatchTranslator $matchTranslator,
        SpecificationTranslator $specificationTranslator,
        Entity $metadata
    ) {
        $this->unitOfWork = $unitOfWork;
        $this->matchTranslator = $matchTranslator;
        $this->specificationTranslator = $specificationTranslator;
        $this->metadata = $metadata;
        $this->allowedStates = (new Set('int'))
            ->add(Container::STATE_NEW)
            ->add(Container::STATE_MANAGED);
    }

    /**
     * {@inheritdoc}
     */
    public function add($entity): RepositoryInterface
    {
        $this->unitOfWork()->persist($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has(Identity $identity): bool
    {
        if ($this->unitOfWork()->contains($identity)) {
            $state = $this->unitOfWork()->stateFor($identity);

            if (!$this->allowedStates->contains($state)) {
                return false;
            }

            return true;
        }

        return (bool) $this->find($identity);
    }

    /**
     * {@inheritdoc}
     */
    public function get(Identity $identity)
    {
        $entity = $this->unitOfWork()->get(
            (string) $this->metadata()->class(),
            $identity
        );
        $state = $this->unitOfWork()->stateFor($identity);

        if (!$this->allowedStates->contains($state)) {
            throw new EntityNotFoundException;
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Identity $identity)
    {
        try {
            return $this->get($identity);
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity): RepositoryInterface
    {
        $this->unitOfWork()->remove($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): SetInterface
    {
        $match = $this->matchTranslator->translate($this->metadata());

        return $this->unitOfWork()->execute(
            $match->query(),
            $match->variables()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function matching(SpecificationInterface $specification): SetInterface
    {
        $match = $this->specificationTranslator->translate(
            $this->metadata(),
            $specification
        );

        return $this->unitOfWork()->execute(
            $match->query(),
            $match->variables()
        );
    }

    /**
     * Return the unit of work
     *
     * @return UnitOfWork
     */
    protected function unitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    /**
     * Return the entity metadata
     */
    protected function metadata(): Entity
    {
        return $this->metadata;
    }
}
