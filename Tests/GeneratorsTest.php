<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Generators;
use Innmind\Neo4j\ONM\GeneratorInterface;
use Innmind\Neo4j\ONM\UnitOfWork;

class GeneratorsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStrategies()
    {
        $this->assertSame(
            ['UUID', 'AUTO'],
            Generators::getStrategies()
        );
    }

    public function testAddGenerator()
    {
        $g = new DummyGenerator;
        Generators::addGenerator($g);

        $this->assertSame(
            $g,
            Generators::getGenerator('dummy')
        );
    }

    public function testGetDefaults()
    {
        $this->assertInstanceOf(
            'Innmind\Neo4j\ONM\Generator\UUIDGenerator',
            Generators::getGenerator('UUID')
        );
        $this->assertInstanceOf(
            'Innmind\Neo4j\ONM\Generator\IdGenerator',
            Generators::getGenerator('AUTO')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowWhenUnknownGenerator()
    {
        Generators::getGenerator('foo');
    }
}

class DummyGenerator implements GeneratorInterface
{
    public function generate(UnitOfWork $uow, $entity)
    {
        return 'foo';
    }

    public function getStrategy()
    {
        return 'dummy';
    }
}
