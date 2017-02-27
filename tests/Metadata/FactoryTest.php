<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testInterface()
    {
        $f = new Factory('Class\Name\SpaceFactory');

        $this->assertSame('Class\Name\SpaceFactory', (string) $f);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyClass()
    {
        new Factory('');
    }
}
