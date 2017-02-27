<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\RelationshipFactory,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
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
    Identity\Generators,
    IdentityInterface,
    Types,
    EntityFactoryInterface
};
use Innmind\Reflection\{
    InstanciatorInterface,
    InjectionStrategyInterface,
    InjectionStrategy\DelegationStrategy
};
use Innmind\Immutable\{
    Map,
    MapInterface,
    SetInterface,
    Set,
    Stream
};
use PHPUnit\Framework\TestCase;

class RelationshipFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityFactoryInterface::class,
            new RelationshipFactory(new Generators)
        );
    }

    /**
     * @dataProvider reflection
     */
    public function testMake($instanciator, $injectionStrategies)
    {
        $factory = new RelationshipFactory(
            new Generators,
            $instanciator,
            $injectionStrategies
        );

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
            new RelationshipEdge('start', Uuid::class, 'target'),
            new RelationshipEdge('end', Uuid::class, 'target')
        );
        $meta = $meta
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )
            );

        $rel = $factory->make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            (new Map('string', 'mixed'))
                ->put('uuid', 24)
                ->put('created', '2016-01-01T00:00:00+0200')
                ->put('start', $start = '11111111-1111-1111-1111-111111111111')
                ->put('end', $end = '11111111-1111-1111-1111-111111111111')
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
        $this->assertInstanceOf(Uuid::class, $rel->start);
        $this->assertInstanceOf(Uuid::class, $rel->end);
        $this->assertSame($start, $rel->start->value());
        $this->assertSame($end, $rel->end->value());
    }

    /**
     * @expectedException Innmind\neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildNonRelationship()
    {
        (new RelationshipFactory(new Generators))->make(
            $this->createMock(IdentityInterface::class),
            $this->createMock(EntityInterface::class),
            new Map('string', 'mixed')
        );
    }

    /**
     * @expectedException Innmind\neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildWithInvalidData()
    {
        (new RelationshipFactory(new Generators))->make(
            $this->createMock(IdentityInterface::class),
            new Relationship(
                new ClassName('foo'),
                new Identity('uuid', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new Alias('foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'target'),
                new RelationshipEdge('end', Uuid::class, 'target')
            ),
            new Map('string', 'variable')
        );
    }

    public function reflection(): array
    {
        return [
            [null, null],
            [
                new class implements InstanciatorInterface {
                    public function build(string $class, MapInterface $properties)
                    {
                        return new $class;
                    }

                    public function parameters(string $class): SetInterface
                    {
                        return new Set('string');
                    }
                },
                null,
            ],
            [
                new class implements InstanciatorInterface {
                    public function build(string $class, MapInterface $properties)
                    {
                        $object = new $class;
                        $properties->foreach(function($name, $value) use ($object) {
                            $object->$name = $value;
                        });

                        return $object;
                    }

                    public function parameters(string $class): SetInterface
                    {
                        return (new Set('string'))
                            ->add('uuid')
                            ->add('created')
                            ->add('start')
                            ->add('end');
                    }
                },
                new DelegationStrategy(
                    new Stream(InjectionStrategyInterface::class)
                ),
            ],
        ];
    }
}
