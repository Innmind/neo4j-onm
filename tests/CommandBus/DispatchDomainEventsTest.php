<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\DispatchDomainEvents,
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBusInterface;
use Innmind\EventBus\{
    EventBusInterface,
    ContainsRecordedEventsInterface,
    EventRecorder,
};
use PHPUnit\Framework\TestCase;

class DispatchDomainEventsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBusInterface::class,
            new DispatchDomainEvents(
                $this->createMock(CommandBusInterface::class),
                $this->createMock(EventBusInterface::class),
                new Container
            )
        );
    }

    public function testHandle()
    {
        $command = new \stdClass;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects($this->once())
            ->method('handle')
            ->with($command);
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->with($this->callback(function($event): bool {
                return $event instanceof \stdClass;
            }));
        $container = new Container;
        $container
            ->push(
                $this->createMock(Identity::class),
                new class {},
                State::new()
            )
            ->push(
                $this->createMock(Identity::class),
                new class implements ContainsRecordedEventsInterface {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::new()
            )
            ->push(
                $this->createMock(Identity::class),
                new class {},
                State::managed()
            )
            ->push(
                $this->createMock(Identity::class),
                new class implements ContainsRecordedEventsInterface {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::managed()
            )
            ->push(
                $this->createMock(Identity::class),
                new class {},
                State::toBeRemoved()
            )
            ->push(
                $this->createMock(Identity::class),
                new class implements ContainsRecordedEventsInterface {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::toBeRemoved()
            )
            ->push(
                $this->createMock(Identity::class),
                new class {},
                State::removed()
            )
            ->push(
                $this->createMock(Identity::class),
                new class implements ContainsRecordedEventsInterface {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::removed()
            );
        $bus = new DispatchDomainEvents($commandBus, $eventBus, $container);

        $this->assertNull($bus->handle($command));
    }
}
