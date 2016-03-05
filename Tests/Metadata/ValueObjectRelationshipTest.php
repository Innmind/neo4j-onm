<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\ValueObjectRelationship,
    Metadata\ClassName,
    Metadata\RelationshipType,
    Metadata\Property,
    TypeInterface
};
use Innmind\Immutable\MapInterface;

class ValueObjectRelationshipTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $vor = new ValueObjectRelationship(
            $cn = new ClassName('foo'),
            $rt = new RelationshipType('FOO'),
            'relationship',
            'node',
            false
        );

        $this->assertSame($cn, $vor->class());
        $this->assertSame($rt, $vor->type());
        $this->assertSame('relationship', $vor->property());
        $this->assertSame('node', $vor->childProperty());
        $this->assertFalse($vor->isCollection());
        $this->assertInstanceOf(MapInterface::class, $vor->properties());
        $this->assertSame('string', (string) $vor->properties()->keyType());
        $this->assertSame(Property::class, (string) $vor->properties()->valueType());
        $this->assertSame(0, $vor->properties()->count());

        $vor2 = $vor->withProperty('foo', $this->getMock(TypeInterface::class));

        $this->assertNotSame($vor, $vor2);
        $this->assertInstanceOf(ValueObjectRelationship::class, $vor2);
        $this->assertSame(0, $vor->properties()->count());
        $this->assertSame(1, $vor2->properties()->count());
        $this->assertTrue($vor2->properties()->contains('foo'));
    }
}
