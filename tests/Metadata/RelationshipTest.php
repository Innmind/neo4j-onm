<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Entity,
    Metadata\ValueObject,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Type,
    EntityFactory\RelationshipFactory,
    Repository\Repository,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipTest extends TestCase
{
    public function testInterface()
    {
        $r = Relationship::of(
            $cn = new ClassName('foo'),
            $i = new Identity('uuid', 'UUID'),
            $t = new RelationshipType('foo'),
            $s = new RelationshipEdge('start', 'UUID', 'target'),
            $e = new RelationshipEdge('end', 'UUID', 'target'),
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertInstanceOf(Entity::class, $r);
        $this->assertSame($cn, $r->class());
        $this->assertSame($i, $r->identity());
        $this->assertSame(Repository::class, (string) $r->repository());
        $this->assertSame(RelationshipFactory::class, (string) $r->factory());
        $this->assertSame($t, $r->type());
        $this->assertSame($s, $r->startNode());
        $this->assertSame($e, $r->endNode());
        $this->assertSame(1, $r->properties()->count());
        $this->assertTrue($r->properties()->contains('foo'));
    }
}

