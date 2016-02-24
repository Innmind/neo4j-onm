<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\AggregateRoot;
use Innmind\Neo4j\ONM\Metadata\ClassName;
use Innmind\Neo4j\ONM\Metadata\Identity;
use Innmind\Neo4j\ONM\Metadata\Repository;
use Innmind\Neo4j\ONM\Metadata\Factory;
use Innmind\Neo4j\ONM\Metadata\Alias;
use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Neo4j\ONM\Metadata\ValueObject;
use Innmind\Immutable\CollectionInterface;
use Innmind\Immutable\TypedCollectionInterface;

class AggregateRootTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $ar = new AggregateRoot(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid'),
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
        $this->assertInstanceOf(CollectionInterface::class, $ar->labels());
        $this->assertSame(['LabelA'], $ar->labels()->toPrimitive());
        $this->assertInstanceOf(TypedCollectionInterface::class, $ar->children());
        $this->assertSame(ValueObject::class, $ar->children()->getType());
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
        $this->assertSame($vo, $ar2->children()->first());
    }
}

