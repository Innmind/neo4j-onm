<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\DispatchDomainEvents,
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBus;
use Innmind\EventBus\{
    EventBus,
    ContainsRecordedEvents,
    EventRecorder,
};
use PHPUnit\Framework\TestCase;

class DispatchDomainEventsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBus::class,
            new DispatchDomainEvents(
                $this->createMock(CommandBus::class),
                $this->createMock(EventBus::class),
                new Container
            )
        );
    }

    public function testHandle()
    {
        $command = new \stdClass;
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus
            ->expects($this->once())
            ->method('__invoke')
            ->with($command);
        $eventBus = $this->createMock(EventBus::class);
        $eventBus
            ->expects($this->exactly(4))
            ->method('__invoke')
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
                new class implements ContainsRecordedEvents {
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
                new class implements ContainsRecordedEvents {
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
                new class implements ContainsRecordedEvents {
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
                new class implements ContainsRecordedEvents {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::removed()
            );
        $handle = new DispatchDomainEvents($commandBus, $eventBus, $container);

        $this->assertNull($handle($command));
    }
}
