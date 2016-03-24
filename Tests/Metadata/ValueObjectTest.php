<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\ClassName,
    Metadata\Property,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};

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
        $this->assertInstanceOf(SetInterface::class, $vo->labels());
        $this->assertSame('string', (string) $vo->labels()->type());
        $this->assertSame(['LabelA', 'LabelB'], $vo->labels()->toPrimitive());
        $this->assertSame($vor, $vo->relationship());
        $this->assertInstanceOf(MapInterface::class, $vo->properties());
        $this->assertSame('string', (string) $vo->properties()->keyType());
        $this->assertSame(Property::class, (string) $vo->properties()->valueType());
        $this->assertSame(0, $vo->properties()->count());

        $vo2 = $vo->withProperty('foo', $this->getMock(TypeInterface::class));

        $this->assertNotSame($vo, $vo2);
        $this->assertInstanceOf(ValueObject::class, $vo2);
        $this->assertSame(0, $vo->properties()->count());
        $this->assertSame(1, $vo2->properties()->count());
        $this->assertTrue($vo2->properties()->contains('foo'));
    }
}
