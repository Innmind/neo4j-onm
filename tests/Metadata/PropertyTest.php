<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Property,
    Type,
};
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    public function testInterface()
    {
        $property = new Property(
            'foo',
            $type = $this->createMock(Type::class)
        );

        $this->assertSame('foo', $property->name());
        $this->assertSame($type, $property->type());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        new Property('', $this->createMock(Type::class));
    }
}
