<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\EntityInterface,
    Metadata\ValueObject,
    TypeInterface
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};

class AggregateTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $ar = new Aggregate(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid', 'UUID'),
            $r = new Repository('Class'),
            $f = new Factory('AnotherClass'),
            $a = new Alias('CanBeClassName'),
            ['LabelA']
        );

        $this->assertInstanceOf(EntityInterface::class, $ar);
        $this->assertSame($cn, $ar->class());
        $this->assertSame($i, $ar->identity());
        $this->assertSame($r, $ar->repository());
        $this->assertSame($f, $ar->factory());
        $this->assertSame($a, $ar->alias());
        $this->assertInstanceOf(SetInterface::class, $ar->labels());
        $this->assertSame('string', (string) $ar->labels()->type());
        $this->assertSame(['LabelA'], $ar->labels()->toPrimitive());
        $this->assertInstanceOf(MapInterface::class, $ar->children());
        $this->assertSame('string', (string) $ar->children()->keyType());
        $this->assertSame(ValueObject::class, (string) $ar->children()->valueType());
        $this->assertSame(0, $ar->children()->count());

        $ar2 = $ar->withChild(
            $vo = $this
                ->getMockBuilder(ValueObject::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $this->assertNotSame($ar, $ar2);
        $this->assertInstanceOf(Aggregate::class, $ar2);
        $this->assertSame(0, $ar->children()->count());
        $this->assertSame(1, $ar2->children()->count());
        $this->assertSame($vo, $ar2->children()->first()->value());

        $ar2 = $ar->withProperty(
            'foo',
            $this->getMock(TypeInterface::class)
        );
        $this->assertNotSame($ar, $ar2);
        $this->assertSame(0, $ar->properties()->count());
        $this->assertSame(1, $ar2->properties()->count());
        $this->assertTrue($ar2->properties()->contains('foo'));
    }
}

