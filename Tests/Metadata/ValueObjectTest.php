<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\ValueObject;
use Innmind\Neo4j\ONM\Metadata\ValueObjectRelationship;
use Innmind\Neo4j\ONM\Metadata\ClassName;
use Innmind\Neo4j\ONM\Metadata\Property;
use Innmind\Neo4j\ONM\TypeInterface;
use Innmind\Immutable\CollectionInterface;
use Innmind\Immutable\TypedCollectionInterface;

class ValueObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $vo = new ValueObject(
            $cn = new ClassName('foo'),
            ['LabelA', 'LabelB'],
            $vor = $this
                ->getMockBuilder(ValueObjectRelationship::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $this->assertSame($cn, $vo->class());
        $this->assertInstanceOf(CollectionInterface::class, $vo->labels());
        $this->assertSame(['LabelA', 'LabelB'], $vo->labels()->toPrimitive());
        $this->assertSame($vor, $vo->relationship());
        $this->assertInstanceOf(TypedCollectionInterface::class, $vo->properties());
        $this->assertSame(Property::class, $vo->properties()->getType());
        $this->assertSame(0, $vo->properties()->count());

        $vo2 = $vo->withProperty('foo', $this->getMock(TypeInterface::class));

        $this->assertNotSame($vo, $vo2);
        $this->assertInstanceOf(ValueObject::class, $vo2);
        $this->assertSame(0, $vo->properties()->count());
        $this->assertSame(1, $vo2->properties()->count());
        $this->assertTrue($vo2->properties()->hasKey('foo'));
    }
}
