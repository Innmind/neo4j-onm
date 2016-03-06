<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\RelationshipFactory,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    IdentityInterface
};
use Innmind\Immutable\{
    Collection,
    SetInterface
};

class RelationshipFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testMake()
    {
        $f = new RelationshipFactory;

        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $meta = new Relationship(
            new ClassName(get_class($entity)),
            new Identity('uuid', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new Identity('start', 'foo'),
            new Identity('end', 'foo')
        );
        $meta = $meta
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    new Collection(['nullable' => null])
                )
            );

        $rel = $f->make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            new Collection([
                'uuid' => 24,
                'created' => '2016-01-01T00:00:00+0200',
                'start' => $start = new Uuid('11111111-1111-1111-1111-111111111111'),
                'end' => $end = new Uuid('11111111-1111-1111-1111-111111111111'),
            ])
        );

        $this->assertInstanceOf(get_class($entity), $rel);
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
        $this->assertSame($start, $rel->start);
        $this->assertSame($end, $rel->end);
    }

    /**
     * @expectedException Innmind\neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildNonRelationship()
    {
        (new RelationshipFactory)->make(
            $this->getMock(IdentityInterface::class),
            $this->getMock(EntityInterface::class),
            new Collection([])
        );
    }
}
