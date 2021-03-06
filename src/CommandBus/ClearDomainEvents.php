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
use Innmind\Immutable\Map;

final class ClearDomainEvents implements CommandBus
{
    private CommandBus $handle;
    private Container $entities;

    public function __construct(CommandBus $handle, Container $entities)
    {
        $this->handle = $handle;
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
        $entitiesWithRecordedEvents->foreach(static function(Identity $identity, ContainsRecordedEvents $entity): void {
            $entity->clearEvents();
        });
    }
}
