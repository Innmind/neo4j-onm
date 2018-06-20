<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\ClearDomainEvents,
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBusInterface;
use Innmind\EventBus\{
    ContainsRecordedEventsInterface,
    EventRecorder,
};
use PHPUnit\Framework\TestCase;

class ClearDomainEventsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBusInterface::class,
            new ClearDomainEvents(
                $this->createMock(CommandBusInterface::class),
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
        $container = new Container;
        $container
            ->push(
                $this->createMock(Identity::class),
                new class {},
                State::new()
            )
            ->push(
                $this->createMock(Identity::class),
                $entity1 = new class implements ContainsRecordedEventsInterface {
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
                $entity2 = new class implements ContainsRecordedEventsInterface {
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
                $entity3 = new class implements ContainsRecordedEventsInterface {
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
                $entity4 = new class implements ContainsRecordedEventsInterface {
                    use EventRecorder;

                    public function __construct()
                    {
                        $this->record(new \stdClass);
                    }
                },
                State::removed()
            );
        $bus = new ClearDomainEvents($commandBus, $container);

        $this->assertNull($bus->handle($command));
        $this->assertCount(0, $entity1->recordedEvents());
        $this->assertCount(0, $entity2->recordedEvents());
        $this->assertCount(0, $entity3->recordedEvents());
        $this->assertCount(0, $entity4->recordedEvents());
    }
}
