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
        $relationship = Relationship::of(
            $className = new ClassName('foo'),
            $identity = new Identity('uuid', 'UUID'),
            $type = new RelationshipType('foo'),
            $start = new RelationshipEdge('start', 'UUID', 'target'),
            $end = new RelationshipEdge('end', 'UUID', 'target'),
            Map::of('string', Type::class)
                ('foo', $this->createMock(Type::class))
        );

        $this->assertInstanceOf(Entity::class, $relationship);
        $this->assertSame($className, $relationship->class());
        $this->assertSame($identity, $relationship->identity());
        $this->assertSame(Repository::class, (string) $relationship->repository());
        $this->assertSame(RelationshipFactory::class, (string) $relationship->factory());
        $this->assertSame($type, $relationship->type());
        $this->assertSame($start, $relationship->startNode());
        $this->assertSame($end, $relationship->endNode());
        $this->assertSame(1, $relationship->properties()->count());
        $this->assertTrue($relationship->properties()->contains('foo'));
    }
}

