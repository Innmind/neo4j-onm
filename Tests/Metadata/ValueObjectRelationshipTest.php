<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\ValueObjectRelationship;
use Innmind\Neo4j\ONM\Metadata\ClassName;
use Innmind\Neo4j\ONM\Metadata\RelationshipType;
use Innmind\Neo4j\ONM\Metadata\Property;
use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\TypedCollectionInterface;

class ValueObjectRelationshipTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $vor = new ValueObjectRelationship(
            $cn = new ClassName('foo'),
            $rt = new RelationshipType('FOO'),
            'relationship',
            'node'
        );

        $this->assertSame($cn, $vor->class());
        $this->assertSame($rt, $vor->type());
        $this->assertSame('relationship', $vor->property());
        $this->assertSame('node', $vor->childProperty());
        $this->assertInstanceOf(TypedCollectionInterface::class, $vor->properties());
        $this->assertSame(Property::class, $vor->properties()->getType());
        $this->assertSame(0, $vor->properties()->count());

        $vor2 = $vor->withProperty('foo', $this->getMock(TypeInterface::class));

        $this->assertNotSame($vor, $vor2);
        $this->assertInstanceOf(ValueObjectRelationship::class, $vor2);
        $this->assertSame(0, $vor->properties()->count());
        $this->assertSame(1, $vor2->properties()->count());
        $this->assertTrue($vor2->properties()->hasKey('foo'));
    }
}
