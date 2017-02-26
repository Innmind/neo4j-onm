<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\Identity\{
    Generators,
    GeneratorInterface,
    Uuid,
    Generator\UuidGenerator
};
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class GeneratorsTest extends TestCase
{
    public function testInterface()
    {
        $generators = new Generators;

        $this->assertInstanceOf(MapInterface::class, $generators->all());
        $this->assertSame('string', (string) $generators->all()->keyType());
        $this->assertSame(GeneratorInterface::class, (string) $generators->all()->valueType());
        $this->assertCount(1, $generators->all());
        $this->assertSame(
            Uuid::class,
            $generators->all()->keys()->current()
        );
        $this->assertInstanceOf(
            UuidGenerator::class,
            $generators->get(Uuid::class)
        );
        $this->assertSame(
            $generators,
            $generators->register('foo', $mock = $this->createMock(GeneratorInterface::class))
        );
        $this->assertCount(2, $generators->all());
        $this->assertSame($mock, $generators->all()->get('foo'));
    }
}
