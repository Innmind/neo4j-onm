<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Metadata\Entity,
};
use Innmind\Immutable\Map;

final class RepositoryFactory
{
    private UnitOfWork $unitOfWork;
    private MatchTranslator $matchTranslator;
    private SpecificationTranslator $specificationTranslator;
    /** @var Map<Entity, Repository> */
    private Map $repositories;

    /**
     * @param Map<Entity, Repository>|null $repositories
     */
    public function __construct(
        UnitOfWork $unitOfWork,
        MatchTranslator $matchTranslator,
        SpecificationTranslator $specificationTranslator,
        Map $repositories = null
    ) {
        /** @var Map<Entity, Repository> */
        $repositories ??= Map::of(Entity::class, Repository::class);

        if (
            (string) $repositories->keyType() !== Entity::class ||
            (string) $repositories->valueType() !== Repository::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type Map<%s, %s>',
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
     * Return the instance of the given entity metadata
     */
    public function __invoke(Entity $meta): Repository
    {
        if ($this->repositories->contains($meta)) {
            return $this->repositories->get($meta);
        }

        $class = $meta->repository()->toString();
        /** @var Repository */
        $repository = new $class(
            $this->unitOfWork,
            $this->matchTranslator,
            $this->specificationTranslator,
            $meta
        );
        $this->register($meta, $repository);

        return $repository;
    }

    /**
     * Register a new repository instance
     *
     * To be used in case the repository can't be instanciated automatically
     */
    private function register(
        Entity $meta,
        Repository $repository
    ): self {
        $this->repositories = $this->repositories->put(
            $meta,
            $repository
        );

        return $this;
    }
}
