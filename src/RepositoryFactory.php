<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Metadata\Entity
};
use Innmind\Immutable\{
    MapInterface,
    Map
};

class RepositoryFactory
{
    private $unitOfWork;
    private $matchTranslator;
    private $specificationTranslator;
    private $repositories;

    public function __construct(
        UnitOfWork $unitOfWork,
        MatchTranslator $matchTranslator,
        SpecificationTranslator $specificationTranslator,
        MapInterface $repositories = null
    ) {
        $repositories = $repositories ?? new Map(Entity::class, Repository::class);

        if (
            (string) $repositories->keyType() !== Entity::class ||
            (string) $repositories->valueType() !== Repository::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type MapInterface<%s, %s>',
                Entity::class,
                Repository::class
            ));
        }

        $this->unitOfWork = $unitOfWork;
        $this->matchTranslator = $matchTranslator;
        $this->specificationTranslator = $specificationTranslator;
        $this->repositories = $repositories;
    }

    /**
     * Register a new repository instance
     *
     * To be used in case the repository can't be instanciated automatically
     */
    public function register(
        Entity $meta,
        Repository $repository
    ): self {
        @trigger_error('Inject repositories in the constructor', E_USER_DEPRECATED);

        $this->repositories = $this->repositories->put(
            $meta,
            $repository
        );

        return $this;
    }

    /**
     * Return the instance of the given entity metadata
     */
    public function make(Entity $meta): Repository
    {
        if ($this->repositories->contains($meta)) {
            return $this->repositories->get($meta);
        }

        $class = (string) $meta->repository();
        $repository = new $class(
            $this->unitOfWork,
            $this->matchTranslator,
            $this->specificationTranslator,
            $meta
        );
        $this->register($meta, $repository);

        return $repository;
    }
}
