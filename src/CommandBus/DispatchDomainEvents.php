<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBusInterface;
use Innmind\EventBus\{
    EventBusInterface,
    ContainsRecordedEventsInterface,
};
use Innmind\Immutable\Stream;

final class DispatchDomainEvents implements CommandBusInterface
{
    private $commandBus;
    private $eventBus;
    private $entities;

    public function __construct(
        CommandBusInterface $commandBus,
        EventBusInterface $eventBus,
        Container $entities
    ) {
        $this->commandBus = $commandBus;
        $this->eventBus = $eventBus;
        $this->entities = $entities;
    }

    public function handle($command)
    {
        $this->commandBus->handle($command);
        $this
            ->entities
            ->state(State::new())
            ->merge($this->entities->state(State::managed()))
            ->merge($this->entities->state(State::toBeRemoved()))
            ->merge($this->entities->state(State::removed()))
            ->filter(function(Identity $identity, $entity): bool {
                return $entity instanceof ContainsRecordedEventsInterface;
            })
            ->reduce(
                new Stream('object'),
                function(
                    Stream $carry,
                    Identity $identity,
                    ContainsRecordedEventsInterface $entity
                ): Stream {
                    return $carry->append($entity->recordedEvents());
                }
            )
            ->foreach(function($event): void {
                $this->eventBus->dispatch($event);
            });
    }
}
