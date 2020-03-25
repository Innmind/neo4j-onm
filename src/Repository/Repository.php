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
    Entity\Container\State,
    Exception\EntityNotFound,
};
use Innmind\Immutable\Set;
use Innmind\Specification\Specification;

final class Repository implements RepositoryInterface
{
    private UnitOfWork $unitOfWork;
    private MatchTranslator $all;
    private SpecificationTranslator $matching;
    private Entity $metadata;
    private Set $allowedStates;

    public function __construct(
        UnitOfWork $unitOfWork,
        MatchTranslator $all,
        SpecificationTranslator $matching,
        Entity $metadata
    ) {
        $this->unitOfWork = $unitOfWork;
        $this->all = $all;
        $this->matching = $matching;
        $this->metadata = $metadata;
        $this->allowedStates = Set::of(
            State::class,
            State::new(),
            State::managed(),
        );
    }

    public function add(object $entity): RepositoryInterface
    {
        $this->unitOfWork()->persist($entity);

        return $this;
    }

    public function contains(Identity $identity): bool
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

    public function get(Identity $identity): object
    {
        $entity = $this->unitOfWork()->get(
            $this->metadata()->class()->toString(),
            $identity,
        );
        $state = $this->unitOfWork()->stateFor($identity);

        if (!$this->allowedStates->contains($state)) {
            throw new EntityNotFound;
        }

        return $entity;
    }

    public function find(Identity $identity): ?object
    {
        try {
            return $this->get($identity);
        } catch (EntityNotFound $e) {
            return null;
        }
    }

    public function remove(object $entity): RepositoryInterface
    {
        $this->unitOfWork()->remove($entity);

        return $this;
    }

    public function all(): Set
    {
        $match = ($this->all)($this->metadata());

        return $this->unitOfWork()->execute(
            $match->query(),
            $match->variables(),
        );
    }

    public function matching(Specification $specification): Set
    {
        $match = ($this->matching)(
            $this->metadata(),
            $specification,
        );

        return $this->unitOfWork()->execute(
            $match->query(),
            $match->variables(),
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
