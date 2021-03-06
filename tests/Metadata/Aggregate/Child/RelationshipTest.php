<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata\Aggregate\Child;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate\Child\Relationship,
    Metadata\ClassName,
    Metadata\RelationshipType,
    Metadata\Property,
    Type,
    Exception\DomainException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
{
    public function testInterface()
    {
        $valueObjectRelationship = Relationship::of(
            $className = new ClassName('foo'),
            $relationshipType = new RelationshipType('FOO'),
            'relationship',
            'node',
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertSame($className, $valueObjectRelationship->class());
        $this->assertSame($relationshipType, $valueObjectRelationship->type());
        $this->assertSame('relationship', $valueObjectRelationship->property());
        $this->assertSame('node', $valueObjectRelationship->childProperty());
        $this->assertInstanceOf(Map::class, $valueObjectRelationship->properties());
        $this->assertSame('string', (string) $valueObjectRelationship->properties()->keyType());
        $this->assertSame(Property::class, (string) $valueObjectRelationship->properties()->valueType());
        $this->assertSame(1, $valueObjectRelationship->properties()->count());
        $this->assertTrue($valueObjectRelationship->properties()->contains('foo'));
    }

    public function testThrowWhenEmptyProperty()
    {
        $this->expectException(DomainException::class);

        Relationship::of(
            new ClassName('foo'),
            new RelationshipType('FOO'),
            '',
            'node'
        );
    }

    public function testThrowWhenEmptyChildProperty()
    {
        $this->expectException(DomainException::class);

        Relationship::of(
            new ClassName('foo'),
            new RelationshipType('FOO'),
            'relationship',
            ''
        );
    }
}
