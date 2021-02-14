<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBus;
use Innmind\EventBus\{
    EventBus,
    ContainsRecordedEvents,
};
use Innmind\Immutable\{
    Sequence,
    Map,
};

final class DispatchDomainEvents implements CommandBus
{
    private CommandBus $handle;
    private EventBus $dispatch;
    private Container $entities;

    public function __construct(
        CommandBus $handle,
        EventBus $dispatch,
        Container $entities
    ) {
        $this->handle = $handle;
        $this->dispatch = $dispatch;
        $this->entities = $entities;
    }

    public function __invoke(object $command): void
    {
        ($this->handle)($command);
        /** @var Map<Identity, ContainsRecordedEvents> */
        $entitiesWithRecordedEvents = $this
            ->entities
            ->state(State::new())
            ->merge($this->entities->state(State::managed()))
            ->merge($this->entities->state(State::toBeRemoved()))
            ->merge($this->entities->state(State::removed()))
            ->filter(static function(Identity $identity, $entity): bool {
                return $entity instanceof ContainsRecordedEvents;
            });
        $entitiesWithRecordedEvents
            ->reduce(
                Sequence::objects(),
                static function(
                    Sequence $carry,
                    Identity $identity,
                    ContainsRecordedEvents $entity
                ): Sequence {
                    return $carry->append($entity->recordedEvents());
                }
            )
            ->foreach(function(object $event): void {
                /** @psalm-suppress MixedArgument */
                ($this->dispatch)($event);
            });
    }
}
