<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\ChangesetComputer,
    Entity\DataExtractor\DataExtractor,
    Translation\ResultTranslator,
    Translation\IdentityMatch\DelegationTranslator as IdentityMatchTranslator,
    Translation\Match\DelegationTranslator as MatchTranslator,
    Translation\Specification\DelegationTranslator as SpecificationTranslator,
    Identity\Generators,
    Identity\Generator,
    EntityFactory\Resolver,
    EntityFactory\RelationshipFactory,
    EntityFactory,
    Persister\DelegationPersister,
    Persister\InsertPersister,
    Persister\UpdatePersister,
    Persister\RemovePersister
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\EventBus\{
    EventBusInterface,
    NullEventBus
};
use Innmind\Immutable\{
    Set,
    Stream,
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
    private $types = [];
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
        $this->eventBus = new NullEventBus;
        $this->entities = $entities;
        $this->config = new Configuration;
        $this->additionalGenerators = new Map('string', Generator::class);
        $this->entityFactories = new Set(EntityFactory::class);
        $this->repositories = new Map('string', Repository::class);
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
     */
    public function withConnection(Connection $connection): self
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
     * @param MapInterface<string, EntityTranslator> $translators
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
     */
    public function withGenerator(string $class, Generator $generator): self
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
     * @param EntityFactory $factory
     *
     * @return self
     */
    public function withEntityFactory(EntityFactory $factory): self
    {
        $this->entityFactories = $this->entityFactories->add($factory);

        return $this;
    }

    /**
     * Set the identity match translators
     *
     * @param MapInterface<string, IdentityMatchTranslator> $translators
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
     * @param MapInterface<string, MetadataFactory> $factories
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
        $this->types[] = $class;

        return $this;
    }

    /**
     * Specify the persister to use
     *
     * @param Persister $persister
     *
     * @return self
     */
    public function withPersister(Persister $persister): self
    {
        $this->persister = $persister;

        return $this;
    }

    /**
     * Specify the translators to use to build match queries
     *
     * @param MapInterface<string, MatchTranslator> $translators
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
     * @param MapInterface<string, SpecificationTranslator> $translators
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
     */
    public function withRepository(string $class, Repository $repository): self
    {
        $this->repositories = $this->repositories->put(
            $class,
            $repository
        );

        return $this;
    }

    /**
     * Return the manager instance
     */
    public function build(): Manager
    {
        return new Manager\Manager(
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
     */
    private function entityFactory(): EntityFactory\EntityFactory
    {
        if ($this->entityFactory === null) {
            $this->entityFactory = new EntityFactory\EntityFactory(
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
            $generators = $this
                ->additionalGenerators
                ->reduce(
                    new Map('string', Generator::class),
                    function(Map $carry, string $class, Generator $gen): Map {
                        return $carry->put($class, $gen);
                    }
                );
            $this->generators = new Generators($generators);
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
                    function(array $carry, EntityFactory $factory): array {
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
                new Types(...$this->types),
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
     * @return Persister
     */
    private function persister(): Persister
    {
        if ($this->persister === null) {
            $this->persister = new DelegationPersister(
                (new Stream(Persister::class))
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
                ->foreach(function(string $class, Repository $repo) {
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
