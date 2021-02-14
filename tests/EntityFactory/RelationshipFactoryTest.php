<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\RelationshipFactory,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Identity\Generators,
    Identity as IdentityInterface,
    Type,
    EntityFactory,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityFactory::class,
            new RelationshipFactory(new Generators)
        );
    }

    public function testMake()
    {
        $make = new RelationshipFactory(new Generators);

        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $meta = Relationship::of(
            new ClassName(\get_class($entity)),
            new Identity('uuid', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'target'),
            new RelationshipEdge('end', Uuid::class, 'target'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
        );

        $rel = $make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            Map::of('string', 'mixed')
                ('uuid', 24)
                ('created', '2016-01-01T00:00:00+0200')
                ('start', $start = '11111111-1111-1111-1111-111111111111')
                ('end', $end = '11111111-1111-1111-1111-111111111111')
        );

        $this->assertInstanceOf(\get_class($entity), $rel);
        $this->assertSame($identity, $rel->uuid);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $rel->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $rel->created->format('c')
        );
        $this->assertSame(null, $rel->empty);
        $this->assertInstanceOf(Uuid::class, $rel->start);
        $this->assertInstanceOf(Uuid::class, $rel->end);
        $this->assertSame($start, $rel->start->value());
        $this->assertSame($end, $rel->end->value());
    }

    public function testThrowWhenTryingToBuildNonRelationship()
    {
        $this->expectException(InvalidArgumentException::class);

        (new RelationshipFactory(new Generators))(
            $this->createMock(IdentityInterface::class),
            $this->createMock(Entity::class),
            Map::of('string', 'mixed')
        );
    }

    public function testThrowWhenTryingToBuildWithInvalidData()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Map<string, mixed>');

        (new RelationshipFactory(new Generators))(
            $this->createMock(IdentityInterface::class),
            Relationship::of(
                new ClassName('foo'),
                new Identity('uuid', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'target'),
                new RelationshipEdge('end', Uuid::class, 'target')
            ),
            Map::of('string', 'variable')
        );
    }
}
