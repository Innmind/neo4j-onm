<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\{
    AggregateRoot,
    ClassName,
    Identity,
    Repository,
    Factory,
    Alias,
    EntityInterface,
    ValueObject
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};

class AggregateRootTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $ar = new AggregateRoot(
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
        $this->assertInstanceOf(AggregateRoot::class, $ar2);
        $this->assertSame(0, $ar->children()->count());
        $this->assertSame(1, $ar2->children()->count());
        $this->assertSame($vo, $ar2->children()->first()->value());
    }
}

