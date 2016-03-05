<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\{
    Relationship,
    ClassName,
    Identity,
    Repository,
    Factory,
    Alias,
    EntityInterface,
    ValueObject,
    RelationshipType
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
            $s = new Identity('start', 'UUID'),
            $e = new Identity('end', 'UUID')
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
    }
}

