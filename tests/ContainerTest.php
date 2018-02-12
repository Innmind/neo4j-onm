<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Manager,
    Entity\Container
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    Loader\Yaml
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use Symfony\Component\Yaml\Yaml as Parser;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testService()
    {
        $container = (new ContainerBuilder(new Yaml))(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('connection', $this->createMock(Connection::class))
                ->put('metas', [Parser::parse(file_get_contents('fixtures/mapping.yml'))])
        );

        $this->assertInstanceOf(Manager::class, $container->get('manager'));
        $this->assertInstanceOf(Container::class, $container->get('container'));
    }
}
