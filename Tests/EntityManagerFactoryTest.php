<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\EntityManagerFactory;
use Innmind\Neo4j\ONM\EntityManager;
use Innmind\Neo4j\ONM\Configuration;
use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EntityManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testMake()
    {
        $conf = Configuration::create([
            'locations' => ['fixtures/metadata.yml'],
            'reader' => 'yaml',
            'cache' => 'cache',
        ]);
        $d = new EventDispatcher;

        $m = EntityManagerFactory::make(
            [
                'host' => getenv('CI') ? 'localhost' : 'docker',
                'username' => 'neo4j',
                'password' => 'ci',
            ],
            $conf,
            $d
        );

        $this->assertInstanceOf(
            EntityManager::class,
            $m
        );
        $this->assertInstanceOf(
            UnitOfWork::class,
            $m->getUnitOfWork()
        );
        $this->assertInstanceOf(
            EventDispatcherInterface::class,
            $m->getDispatcher()
        );
        $this->assertInstanceOf(
            ConnectionInterface::class,
            $m->getConnection()
        );
        $this->assertSame(
            $d,
            $m->getDispatcher()
        );
    }
}
