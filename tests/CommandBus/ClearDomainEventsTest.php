<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\CommandBus;

use Innmind\Neo4j\ONM\{
    CommandBus\ClearDomainEvents,
    Entity\Container,
    Entity\Container\State,
    Identity,
};
use Innmind\CommandBus\CommandBus;
use Innmind\EventBus\{
    ContainsRecordedEvents,
    EventRecorder,
};
use PHPUnit\Framework\TestCase;

class ClearDomainEventsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CommandBus::class,
            new ClearDomainEvents(
                $this->createMock(CommandBus::class),
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
        $container = new Container;
        $container->push(
            $this->createMock(Identity::class),
            new class {},
            State::new()
        );
        $container->push(
            $this->createMock(Identity::class),
            $entity1 = new class implements ContainsRecordedEvents {
                use EventRecorder;

                public function __construct()
                {
                    $this->record(new \stdClass);
                }
            },
            State::new()
        );
        $container->push(
            $this->createMock(Identity::class),
            new class {},
            State::managed()
        );
        $container->push(
            $this->createMock(Identity::class),
            $entity2 = new class implements ContainsRecordedEvents {
                use EventRecorder;

                public function __construct()
                {
                    $this->record(new \stdClass);
                }
            },
            State::managed()
        );
        $container->push(
            $this->createMock(Identity::class),
            new class {},
            State::toBeRemoved()
        );
        $container->push(
            $this->createMock(Identity::class),
            $entity3 = new class implements ContainsRecordedEvents {
                use EventRecorder;

                public function __construct()
                {
                    $this->record(new \stdClass);
                }
            },
            State::toBeRemoved()
        );
        $container->push(
            $this->createMock(Identity::class),
            new class {},
            State::removed()
        );
        $container->push(
            $this->createMock(Identity::class),
            $entity4 = new class implements ContainsRecordedEvents {
                use EventRecorder;

                public function __construct()
                {
                    $this->record(new \stdClass);
                }
            },
            State::removed()
        );
        $handle = new ClearDomainEvents($commandBus, $container);

        $this->assertNull($handle($command));
        $this->assertCount(0, $entity1->recordedEvents());
        $this->assertCount(0, $entity2->recordedEvents());
        $this->assertCount(0, $entity3->recordedEvents());
        $this->assertCount(0, $entity4->recordedEvents());
    }
}
