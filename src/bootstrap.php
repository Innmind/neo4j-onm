<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate,
    Metadata\Relationship,
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\EventBus\{
    EventBus,
    EventBus\NullEventBus,
};
use Innmind\CommandBus\CommandBus as CommandBusInterface;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
};

/**
 * @param  SetInterface<Metadata\Entity> $metas
 * @param  MapInterface<string, Generator>|null $additionalGenerators
 * @param  MapInterface<Identity, Repository>|null $repositories
 * @param  SetInterface<EntityFactory>|null $entityFactories
 * @param  MapInterface<string, EntityTranslator>|null $resultTranslators
 * @param  MapInterface<string, IdentityMatchTranslator>|null $identityMatchTranslators
 * @param  MapInterface<string, MatchTranslator>|null $matchTranslators
 * @param  MapInterface<string, SpecificationTranslator>|null $specificationTranslators
 * @param  MapInterface<string, DataExtractor>|null $dataExtractors
 */
function bootstrap(
    Connection $connection,
    SetInterface $metas,
    MapInterface $additionalGenerators = null,
    EventBus $eventBus = null,
    MapInterface $repositories = null,
    Persister $persister = null,
    SetInterface $entityFactories = null,
    MapInterface $resultTranslators = null,
    MapInterface $identityMatchTranslators = null,
    MapInterface $matchTranslators = null,
    MapInterface $specificationTranslators = null,
    MapInterface $dataExtractors = null
): array {
    $eventBus = $eventBus ?? new NullEventBus;

    $resultTranslators = $resultTranslators ?? Map::of('string', Translation\EntityTranslator::class)
        (Aggregate::class, new Translation\Result\AggregateTranslator)
        (Relationship::class, new Translation\Result\RelationshipTranslator);
    $identityMatchTranslators = $identityMatchTranslators ?? Map::of('string', Translation\IdentityMatchTranslator::class)
        (Aggregate::class, new Translation\IdentityMatch\AggregateTranslator)
        (Relationship::class, new Translation\IdentityMatch\RelationshipTranslator);
    $matchTranslators = $matchTranslators ?? Map::of('string', Translation\MatchTranslator::class)
        (Aggregate::class, new Translation\Match\AggregateTranslator)
        (Relationship::class, new Translation\Match\RelationshipTranslator);
    $specificationTranslators = $specificationTranslators ?? Map::of('string', Translation\SpecificationTranslator::class)
        (Aggregate::class, new Translation\Specification\AggregateTranslator)
        (Relationship::class, new Translation\Specification\RelationshipTranslator);
    $dataExtractors = $dataExtractors ?? Map::of('string', Entity\DataExtractor::class)
        (Aggregate::class, new Entity\DataExtractor\AggregateExtractor)
        (Relationship::class, new Entity\DataExtractor\RelationshipExtractor);

    $identityGenerators = new Identity\Generators($additionalGenerators);

    $entityFactories = $entityFactories ?? Set::of(
        EntityFactory::class,
        new EntityFactory\AggregateFactory,
        new EntityFactory\RelationshipFactory($identityGenerators)
    );

    $metadatas = new Metadatas(...$metas);

    $entityChangeset = new Entity\ChangesetComputer;
    $dataExtractor = new Entity\DataExtractor\DataExtractor(
        $metadatas,
        $dataExtractors
    );

    $persister = $persister ?? new Persister\DelegationPersister(
        new Persister\InsertPersister(
            $entityChangeset,
            $eventBus,
            $dataExtractor,
            $metadatas
        ),
        new Persister\UpdatePersister(
            $entityChangeset,
            $eventBus,
            $dataExtractor,
            $metadatas
        ),
        new Persister\RemovePersister(
            $entityChangeset,
            $eventBus,
            $metadatas
        )
    );

    $entityContainer = new Entity\Container;

    $unitOfWork = new UnitOfWork(
        $connection,
        $entityContainer,
        new EntityFactory\EntityFactory(
            new Translation\ResultTranslator($resultTranslators),
            $identityGenerators,
            new EntityFactory\Resolver(...$entityFactories),
            $entityContainer
        ),
        new Translation\IdentityMatch\DelegationTranslator($identityMatchTranslators),
        $metadatas,
        $persister,
        $identityGenerators
    );

    $manager = new Manager\Manager(
        $unitOfWork,
        $metadatas,
        new RepositoryFactory(
            $unitOfWork,
            new Translation\Match\DelegationTranslator($matchTranslators),
            new Translation\Specification\DelegationTranslator($specificationTranslators),
            $repositories
        ),
        $identityGenerators
    );

    return [
        'manager' => $manager,
        'command_bus' => [
            'clear_domain_events' => static function(CommandBusInterface $bus) use ($entityContainer): CommandBusInterface {
                return new CommandBus\ClearDomainEvents($bus, $entityContainer);
            },
            'dispatch_domain_events' => static function(CommandBusInterface $bus) use ($eventBus, $entityContainer): CommandBusInterface {
                return new CommandBus\DispatchDomainEvents($bus, $eventBus, $entityContainer);
            },
            'flush' => static function(CommandBusInterface $bus) use ($manager): CommandBusInterface {
                return new CommandBus\Flush($bus, $manager);
            },
            'transaction' => static function(CommandBusInterface $bus) use ($manager): CommandBusInterface {
                return new CommandBus\Transaction($bus, $manager);
            },
        ],
    ];
}
