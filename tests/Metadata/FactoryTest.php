<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Factory,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testInterface()
    {
        $factory = new Factory('Class\Name\SpaceFactory');

        $this->assertSame('Class\Name\SpaceFactory', (string) $factory);
    }

    public function testThrowWhenEmptyClass()
    {
        $this->expectException(DomainException::class);

        new Factory('');
    }
}
