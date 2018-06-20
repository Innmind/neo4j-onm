<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBusInterface;
use Innmind\EventBus\ContainsRecordedEventsInterface;

final class ClearDomainEvents implements CommandBusInterface
{
    private $commandBus;
    private $entities;

    public function __construct(
        CommandBusInterface $commandBus,
        Container $entities
    ) {
        $this->commandBus = $commandBus;
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
            ->foreach(function(
                Identity $identity,
                ContainsRecordedEventsInterface $entity
            ): void {
                $entity->clearEvents();
            });
    }
}
