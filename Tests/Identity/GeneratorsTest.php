<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Identity;

use Innmind\Neo4j\ONM\Identity\{
    Generators,
    GeneratorInterface,
    Uuid,
    Generator\UuidGenerator
};

class GeneratorsTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $g = new Generators;

        $this->assertSame(1, $g->all()->size());
        $this->assertSame(
            Uuid::class,
            $g->all()->keys()->get(0)
        );
        $this->assertInstanceOf(
            UuidGenerator::class,
            $g->get(Uuid::class)
        );
        $this->assertSame(
            $g,
            $g->register('foo', $m = $this->getMock(GeneratorInterface::class))
        );
        $this->assertSame(2, $g->all()->size());
    }
}
