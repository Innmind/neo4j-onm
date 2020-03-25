<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use function Innmind\Neo4j\ONM\bootstrap;
use Innmind\Neo4j\ONM\{
    Manager,
    Metadata\Entity,
    CommandBus\ClearDomainEvents,
    CommandBus\DispatchDomainEvents,
    CommandBus\Flush,
    CommandBus\Transaction,
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\CommandBus\CommandBus;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $metadatas = require 'fixtures/mapping.php';

        $services = bootstrap(
            $this->createMock(Connection::class),
            Set::of(Entity::class, ...$metadatas)
        );

        $this->assertInstanceOf(Manager::class, $services['manager']);
        $bus = $this->createMock(CommandBus::class);
        $this->assertIsCallable($services['command_bus']['clear_domain_events']);
        $this->assertInstanceOf(
            ClearDomainEvents::class,
            $services['command_bus']['clear_domain_events']($bus)
        );
        $this->assertIsCallable($services['command_bus']['dispatch_domain_events']);
        $this->assertInstanceOf(
            DispatchDomainEvents::class,
            $services['command_bus']['dispatch_domain_events']($bus)
        );
        $this->assertIsCallable($services['command_bus']['flush']);
        $this->assertInstanceOf(
            Flush::class,
            $services['command_bus']['flush']($bus)
        );
        $this->assertIsCallable($services['command_bus']['transaction']);
        $this->assertInstanceOf(
            Transaction::class,
            $services['command_bus']['transaction']($bus)
        );
    }
}
