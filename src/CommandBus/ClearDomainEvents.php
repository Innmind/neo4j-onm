<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBus;
use Innmind\EventBus\ContainsRecordedEvents;

final class ClearDomainEvents implements CommandBus
{
    private $handle;
    private $entities;

    public function __construct(CommandBus $handle, Container $entities)
    {
        $this->handle = $handle;
        $this->entities = $entities;
    }

    public function __invoke(object $command): void
    {
        ($this->handle)($command);
        $this
            ->entities
            ->state(State::new())
            ->merge($this->entities->state(State::managed()))
            ->merge($this->entities->state(State::toBeRemoved()))
            ->merge($this->entities->state(State::removed()))
            ->filter(function(Identity $identity, $entity): bool {
                return $entity instanceof ContainsRecordedEvents;
            })
            ->foreach(function(
                Identity $identity,
                ContainsRecordedEvents $entity
            ): void {
                $entity->clearEvents();
            });
    }
}
