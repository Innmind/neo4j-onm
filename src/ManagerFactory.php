<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\ChangesetComputer,
    Entity\DataExtractor,
    Translation\ResultTranslator,
    Translation\IdentityMatchTranslator,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Identity\Generators,
    Identity\GeneratorInterface,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    Persister\DelegationPersister,
    Persister\InsertPersister,
    Persister\UpdatePersister,
    Persister\RemovePersister
};
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Set,
    MapInterface,
    Map
};
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ManagerFactory
{
    private $entities;
    private $config;
    private $connection;
    private $eventBus;
    private $uow;
    private $container;
    private $entityFactory;
    private $entityFactories;
    private $resultTranslator;
    private $generators;
    private $additionalGenerators;
    private $resolver;
    private $identityMatchTranslator;
    private $additionalIdentityMatchTranslator;
    private $types;
    private $metadataBuilder;
    private $metadataFactories;
    private $persister;
    private $changeset;
    private $extractor;
    private $repositoryFactory;
    private $entityTranslators;
    private $matchTranslators;
    private $specificationTranslators;
    private $repositories;

    private function __construct(array $entities)
    {
        $this->entities = $entities;
        $this->types = new Types;
        $this->config = new Configuration;
        $this->additionalGenerators = new Map('string', GeneratorInterface::class);
        $this->entityFactories = new Set(EntityFactoryInterface::class);
        $this->repositories = new Map('string', RepositoryInterface::class);
    }

    /**
     * Specify the entities definitions you want to use
     *
     * @param array $entities
     *
     * @return self
     */
    public static function for(array $entities): self
    {
        return new self($entities);
    }

    /**
     * Specify the configuration object to use to validate config data
     *
     * @param ConfigurationInterface $config
     *
     * @return self
     */
    public function validatedBy(ConfigurationInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Specify the connection to use
     *
     * @param ConnectionInterface $connection
     *
     * @return self
     */
    public function withConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Specify the event event bus to use
     *
     * @param EventBusInterface $eventBus
     *
     * @return self
     */
    public function withEventBus(EventBusInterface $eventBus): self
    {
        $this->eventBus = $eventBus;

        return $this;
    }

    /**
     * Specify the map of translators to use in ResultTranslator
     *
     * @param MapInterface<string, EntityTranslatorInterface> $translators
     *
     * @return self
     */
    public function withEntityTranslators(MapInterface $translators): self
    {
        $this->entityTranslators = $translators;

        return $this;
    }

    /**
     * Add an identity generator
     *
     * @param string $class The identity class the generator generates
     * @param GeneratorInterface $generator
     *
     * @return self
     */
    public function withGenerator(string $class, GeneratorInterface $generator): self
    {
        $this->additionalGenerators = $this->additionalGenerators->put(
            $class,
            $generator
        );

        return $this;
    }

    /**
     * Add an entity factory
     *
     * @param EntityFactoryInterface $factory
     *
     * @return self
     */
    public function withEntityFactory(EntityFactoryInterface $factory): self
    {
        $this->entityFactories = $this->entityFactories->add($factory);

        return $this;
    }

    /**
     * Set the identity match translators
     *
     * @param MapInterface<string, IdentityMatchTranslatorInterface> $translators
     *
     * @return self
     */
    public function withIdentityMatchTranslators(MapInterface $translators): self
    {
        $this->additionalIdentityMatchTranslator = $translators;

        return $this;
    }

    /**
     * Specify the metadata factories
     *
     * @param MapInterface<string, MetadataFactoryInterface> $factories
     *
     * @return self
     */
    public function withMetadataFactories(MapInterface $factories): self
    {
        $this->metadataFactories = $factories;

        return $this;
    }

    /**
     * Add a new property type
     *
     * @param string $class
     *
     * @return self
     */
    public function withType(string $class): self
    {
        $this->types->register($class);

        return $this;
    }

    /**
     * Specify the persister to use
     *
     * @param PersisterInterface $persister
     *
     * @return self
     */
    public function withPersister(PersisterInterface $persister): self
    {
        $this->persister = $persister;

        return $this;
    }

    /**
     * Specify the translators to use to build match queries
     *
     * @param MapInterface<string, MatchTranslatorInterface> $translators
     *
     * @return self
     */
    public function withMatchTranslators(MapInterface $translators): self
    {
        $this->matchTranslators = $translators;

        return $this;
    }

    /**
     * Specify the translators to use to build match queries out of specifications
     *
     * @param MapInterface<string, SpecificationTranslatorInterface> $translators
     *
     * @return self
     */
    public function withSpecificationTranslators(MapInterface $translators): self
    {
        $this->specificationTranslators = $translators;

        return $this;
    }

    /**
     * Add a new repository instance
     *
     * @param string $class Entity class
     * @param RepositoryInterface $repository
     *
     * @return self
     */
    public function withRepository(string $class, RepositoryInterface $repository): self
    {
        $this->repositories = $this->repositories->put(
            $class,
            $repository
        );

        return $this;
    }

    /**
     * Return the manager instance
     *
     * @return ManagerInterface
     */
    public function build(): ManagerInterface
    {
        return new Manager(
            $this->unitOfWork(),
            $this->metadatas(),
            $this->repositoryFactory(),
            $this->generators()
        );
    }

    /**
     * Build the unit of work
     *
     * @return UnitOfWork
     */
    private function unitOfWork(): UnitOfWork
    {
        if ($this->uow === null) {
            $this->uow = new UnitOfWork(
                $this->connection,
                $this->container(),
                $this->entityFactory(),
                $this->identityMatchTranslator(),
                $this->metadatas(),
                $this->persister(),
                $this->generators()
            );
        }

        return $this->uow;
    }

    /**
     * Build an entity container
     *
     * @return Container
     */
    private function container(): Container
    {
        if ($this->container === null) {
            $this->container = new Container;
        }

        return $this->container;
    }

    /**
     * Build the entity factory
     *
     * @return EntityFactory
     */
    private function entityFactory(): EntityFactory
    {
        if ($this->entityFactory === null) {
            $this->entityFactory = new EntityFactory(
                $this->resultTranslator(),
                $this->generators(),
                $this->resolver(),
                $this->container()
            );
        }

        return $this->entityFactory;
    }

    /**
     * Build the dbal query result translator
     *
     * @return ResultTranslator
     */
    private function resultTranslator(): ResultTranslator
    {
        if ($this->resultTranslator === null) {
            $this->resultTranslator = new ResultTranslator(
                $this->entityTranslators
            );
        }

        return $this->resultTranslator;
    }

    /**
     * Build the identities generators
     *
     * @return Generators
     */
    private function generators(): Generators
    {
        if ($this->generators === null) {
            $this->generators = new Generators;
            $this
                ->additionalGenerators
                ->foreach(function(string $class, GeneratorInterface $gen) {
                    $this->generators->register($class, $gen);
                });
        }

        return $this->generators;
    }

    /**
     * Build the entity factory resolver
     *
     * @return Resolver
     */
    private function resolver(): Resolver
    {
        if ($this->resolver === null) {
            $factories = $this
                ->entityFactories
                ->reduce(
                    [new RelationshipFactory($this->generators())],
                    function(array $carry, EntityFactoryInterface $factory): array {
                        $carry[] = $factory;

                        return $carry;
                    }
                );
            $this->resolver = new Resolver(...$factories);
        }

        return $this->resolver;
    }

    /**
     * Build the identity match translator
     *
     * @return IdentityMatchTranslator
     */
    private function identityMatchTranslator(): IdentityMatchTranslator
    {
        if ($this->identityMatchTranslator === null) {
            $this->identityMatchTranslator = new IdentityMatchTranslator(
                $this->additionalIdentityMatchTranslator
            );
        }

        return $this->identityMatchTranslator;
    }

    /**
     * Build the metadatas container
     *
     * @return Metadatas
     */
    private function metadatas(): Metadatas
    {
        if ($this->metadataBuilder === null) {
            $this->metadataBuilder = (new MetadataBuilder(
                $this->types,
                $this->metadataFactories,
                $this->config
            ))
                ->inject($this->entities);
        }

        return $this->metadataBuilder->container();
    }

    /**
     * Build the persister
     *
     * @return PersisterInterface
     */
    private function persister(): PersisterInterface
    {
        if ($this->persister === null) {
            $this->persister = new DelegationPersister(
                (new Set(PersisterInterface::class))
                    ->add(
                        new InsertPersister(
                            $this->changeset(),
                            $this->eventBus,
                            $this->extractor(),
                            $this->metadatas()
                        )
                    )
                    ->add(
                        new UpdatePersister(
                            $this->changeset(),
                            $this->eventBus,
                            $this->extractor(),
                            $this->metadatas()
                        )
                    )
                    ->add(
                        new RemovePersister(
                            $this->changeset(),
                            $this->eventBus,
                            $this->metadatas()
                        )
                    )
            );
        }

        return $this->persister;
    }

    /**
     * Build the changeset computer
     *
     * @return ChangesetComputer
     */
    private function changeset(): ChangesetComputer
    {
        if ($this->changeset === null) {
            $this->changeset = new ChangesetComputer;
        }

        return $this->changeset;
    }

    /**
     * Build the entity data extractor
     *
     * @return DataExtractor
     */
    private function extractor(): DataExtractor
    {
        if ($this->extractor === null) {
            $this->extractor = new DataExtractor($this->metadatas());
        }

        return $this->extractor;
    }

    /**
     * Build the repository factory
     *
     * @return RepositoryFactory
     */
    private function repositoryFactory(): RepositoryFactory
    {
        if ($this->repositoryFactory === null) {
            $this->repositoryFactory = new RepositoryFactory(
                $this->unitOfWork(),
                new MatchTranslator(
                    $this->matchTranslators
                ),
                new SpecificationTranslator(
                    $this->specificationTranslators
                )
            );
            $this
                ->repositories
                ->foreach(function(string $class, RepositoryInterface $repo) {
                    $this
                        ->repositoryFactory
                        ->register(
                            $this->metadatas()->get($class),
                            $repo
                        );
                });
        }

        return $this->repositoryFactory;
    }
}
