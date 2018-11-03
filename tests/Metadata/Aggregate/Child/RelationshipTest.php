<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata\Aggregate\Child;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate\Child\Relationship,
    Metadata\ClassName,
    Metadata\RelationshipType,
    Metadata\Property,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
{
    public function testInterface()
    {
        $vor = Relationship::of(
            $cn = new ClassName('foo'),
            $rt = new RelationshipType('FOO'),
            'relationship',
            'node',
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertSame($cn, $vor->class());
        $this->assertSame($rt, $vor->type());
        $this->assertSame('relationship', $vor->property());
        $this->assertSame('node', $vor->childProperty());
        $this->assertInstanceOf(MapInterface::class, $vor->properties());
        $this->assertSame('string', (string) $vor->properties()->keyType());
        $this->assertSame(Property::class, (string) $vor->properties()->valueType());
        $this->assertSame(1, $vor->properties()->count());
        $this->assertTrue($vor->properties()->contains('foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyProperty()
    {
        Relationship::of(
            new ClassName('foo'),
            new RelationshipType('FOO'),
            '',
            'node'
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyChildProperty()
    {
        Relationship::of(
            new ClassName('foo'),
            new RelationshipType('FOO'),
            'relationship',
            ''
        );
    }
}
