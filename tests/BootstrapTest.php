<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use function Innmind\Neo4j\ONM\bootstrap;
use Innmind\Neo4j\ONM\{
    Manager,
    CommandBus\ClearDomainEvents,
    CommandBus\DispatchDomainEvents,
    CommandBus\Flush,
    CommandBus\Transaction,
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\CommandBus\CommandBusInterface;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $services = bootstrap(
            $this->createMock(Connection::class),
            [Yaml::parse(file_get_contents('fixtures/mapping.yml'))]
        );

        $this->assertInstanceOf(Manager::class, $services['manager']);
        $bus = $this->createMock(CommandBusInterface::class);
        $this->assertInternalType('callable', $services['commandBus']['clear_domain_events']);
        $this->assertInstanceOf(
            ClearDomainEvents::class,
            $services['commandBus']['clear_domain_events']($bus)
        );
        $this->assertInternalType('callable', $services['commandBus']['dispatch_domain_events']);
        $this->assertInstanceOf(
            DispatchDomainEvents::class,
            $services['commandBus']['dispatch_domain_events']($bus)
        );
        $this->assertInternalType('callable', $services['commandBus']['flush']);
        $this->assertInstanceOf(
            Flush::class,
            $services['commandBus']['flush']($bus)
        );
        $this->assertInternalType('callable', $services['commandBus']['transaction']);
        $this->assertInstanceOf(
            Transaction::class,
            $services['commandBus']['transaction']($bus)
        );
    }
}
