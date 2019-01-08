<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Property,
    Type,
    Exception\DomainException,
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

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

        new Property('', $this->createMock(Type::class));
    }
}
