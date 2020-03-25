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
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;

/**
 * @param  Set<Metadata\Entity> $metas
 * @param  Map<string, Identity\Generator>|null $additionalGenerators
 * @param  Map<Metadata\Entity, Repository>|null $repositories
 * @param  Set<EntityFactory>|null $entityFactories
 * @param  Map<string, Translation\EntityTranslator>|null $resultTranslators
 * @param  Map<string, Translation\IdentityMatchTranslator>|null $identityMatchTranslators
 * @param  Map<string, Translation\MatchTranslator>|null $matchTranslators
 * @param  Map<string, Translation\SpecificationTranslator>|null $specificationTranslators
 * @param  Map<string, Entity\DataExtractor>|null $dataExtractors
 *
 * @return array{manager: Manager, command_bus: array{clear_domain_events: callable(CommandBusInterface): CommandBusInterface, dispatch_domain_events: callable(CommandBusInterface): CommandBusInterface, flush: callable(CommandBusInterface): CommandBusInterface, transaction: callable(CommandBusInterface): CommandBusInterface}}
 */
function bootstrap(
    Connection $connection,
    Set $metas,
    Map $additionalGenerators = null,
    EventBus $eventBus = null,
    Map $repositories = null,
    Persister $persister = null,
    Set $entityFactories = null,
    Map $resultTranslators = null,
    Map $identityMatchTranslators = null,
    Map $matchTranslators = null,
    Map $specificationTranslators = null,
    Map $dataExtractors = null
): array {
    $eventBus = $eventBus ?? new NullEventBus;

    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress InvalidArgument
     */
    $resultTranslators ??= Map::of('string', Translation\EntityTranslator::class)
        (Aggregate::class, new Translation\Result\AggregateTranslator)
        (Relationship::class, new Translation\Result\RelationshipTranslator);
    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress InvalidArgument
     */
    $identityMatchTranslators ??= Map::of('string', Translation\IdentityMatchTranslator::class)
        (Aggregate::class, new Translation\IdentityMatch\AggregateTranslator)
        (Relationship::class, new Translation\IdentityMatch\RelationshipTranslator);
    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress InvalidArgument
     */
    $matchTranslators ??= Map::of('string', Translation\MatchTranslator::class)
        (Aggregate::class, new Translation\Match\AggregateTranslator)
        (Relationship::class, new Translation\Match\RelationshipTranslator);
    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress InvalidArgument
     */
    $specificationTranslators ??= Map::of('string', Translation\SpecificationTranslator::class)
        (Aggregate::class, new Translation\Specification\AggregateTranslator)
        (Relationship::class, new Translation\Specification\RelationshipTranslator);
    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress InvalidArgument
     */
    $dataExtractors ??= Map::of('string', Entity\DataExtractor::class)
        (Aggregate::class, new Entity\DataExtractor\AggregateExtractor)
        (Relationship::class, new Entity\DataExtractor\RelationshipExtractor);

    $identityGenerators = new Identity\Generators($additionalGenerators);

    $entityFactories = $entityFactories ?? Set::of(
        EntityFactory::class,
        new EntityFactory\AggregateFactory,
        new EntityFactory\RelationshipFactory($identityGenerators)
    );

    $metadatas = new Metadatas(...unwrap($metas));

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
            new EntityFactory\Resolver(...unwrap($entityFactories)),
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
