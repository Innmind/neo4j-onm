<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Exception\RepositoryException;

class RepositoryFactory
{
    const REPOSITORY_INTERFACE = 'Innmind\Neo4j\ONM\RepositoryInterface';

    protected $repositories = [];
    protected $identityMap;
    protected $metadataRegistry;

    public function __construct(IdentityMap $identityMap, MetadataRegistry $metadataRegistry)
    {
        $this->identityMap = $identityMap;
        $this->metadataRegistry = $metadataRegistry;
    }

    /**
     * Return a repository for the given alias/class
     *
     * @param string $alias Can be the entity alias or its class name
     * @param EntityManagerInterface $em
     *
     * @return Repository
     */
    public function make($alias, EntityManagerInterface $em)
    {
        $class = $this->identityMap->getClass($alias);

        if (isset($this->repositories[$class])) {
            return $this->repositories[$class];
        }

        $metadata = $this->metadataRegistry->getMetadata($class);
        $repoClass = $metadata->getRepositoryClass();
        $refl = new \ReflectionClass($repoClass);

        if (!$refl->implementsInterface(self::REPOSITORY_INTERFACE)) {
            throw new RepositoryException(
                sprintf(
                    'The repository "%s" must implement "%s"',
                    $class,
                    self::REPOSITORY_INTERFACE
                ),
                RepositoryException::INVALID_INSTANCE
            );
        }

        $repository = new $repoClass($em, $class);
        $this->repositories[$class] = $repository;

        return $repository;
    }
}
