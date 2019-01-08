<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\Identity\{
    Generators,
    Generator,
    Uuid,
    Generator\UuidGenerator,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class GeneratorsTest extends TestCase
{
    public function testInterface()
    {
        $generators = new Generators;

        $this->assertInstanceOf(
            UuidGenerator::class,
            $generators->get(Uuid::class)
        );
    }

    public function testRegisterGenerator()
    {
        $generators = new Generators(
            Map::of('string', Generator::class)
                (
                    'foo',
                    $mock = $this->createMock(Generator::class)
                )
        );

        $this->assertSame($mock, $generators->get('foo'));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testTrhowWhenInvalidMap()
    {
        new Generators(new Map('string', 'object'));
    }
}
