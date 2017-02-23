<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\Identity\{
    Generators,
    GeneratorInterface,
    Uuid,
    Generator\UuidGenerator
};
use PHPUnit\Framework\TestCase;

class GeneratorsTest extends TestCase
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
            $g->register('foo', $m = $this->createMock(GeneratorInterface::class))
        );
        $this->assertSame(2, $g->all()->size());
    }
}
