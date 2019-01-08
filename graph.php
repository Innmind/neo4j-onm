<?php
declare(strict_types = 1);

require __DIR__.'/vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Neo4j\ONM\Metadata\Entity;
use function Innmind\Neo4j\ONM\bootstrap;
use function Innmind\Neo4j\DBAL\bootstrap as dbal;
use Innmind\Server\Control\Server\Command;
use Innmind\ObjectGraph\{
    Graph,
    Visualize,
};
use Innmind\Immutable\Set;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $dbal = dbal(
            $os->remote()->http(),
            $os->clock()
        );
        $package = bootstrap(
            $dbal,
            Set::of(Entity::class)
        );

        $graph = new Graph;
        $visualize = new Visualize;

        $os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', 'graph.svg')
                    ->withInput(
                        $visualize($graph($package['manager']))
                    )
            )
            ->wait();
    }
};
