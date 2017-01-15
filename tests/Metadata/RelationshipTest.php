<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\EntityInterface,
    Metadata\ValueObject,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    TypeInterface
};

class RelationshipTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $r = new Relationship(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid', 'UUID'),
            $repo = new Repository('Class'),
            $f = new Factory('AnotherClass'),
            $a = new Alias('CanBeClassName'),
            $t = new RelationshipType('foo'),
            $s = new RelationshipEdge('start', 'UUID', 'target'),
            $e = new RelationshipEdge('end', 'UUID', 'target')
        );

        $this->assertInstanceOf(EntityInterface::class, $r);
        $this->assertSame($cn, $r->class());
        $this->assertSame($i, $r->identity());
        $this->assertSame($repo, $r->repository());
        $this->assertSame($f, $r->factory());
        $this->assertSame($a, $r->alias());
        $this->assertSame($t, $r->type());
        $this->assertSame($s, $r->startNode());
        $this->assertSame($e, $r->endNode());

        $r2 = $r->withProperty(
            'foo',
            $this->getMock(TypeInterface::class)
        );
        $this->assertNotSame($r, $r2);
        $this->assertSame(0, $r->properties()->count());
        $this->assertSame(1, $r2->properties()->count());
        $this->assertTrue($r2->properties()->contains('foo'));
    }
}

