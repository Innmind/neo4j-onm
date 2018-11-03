<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\AggregateFactory,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Identity as IdentityInterface,
    Types,
    Type,
    EntityFactory,
};
use Innmind\Reflection\{
    Instanciator,
    InjectionStrategy,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
    Stream,
};
use PHPUnit\Framework\TestCase;

class AggregateFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityFactory::class,
            new AggregateFactory
        );
    }

    /**
     * @dataProvider reflection
     */
    public function testMake($instanciator, $injectionStrategies)
    {
        $make = new AggregateFactory($instanciator, $injectionStrategies);

        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $rel = new class {
            public $created;
            public $empty;
            public $child;
        };
        $child = new class {
            public $content;
            public $empty;
        };
        $meta = Aggregate::of(
            new ClassName(get_class($entity)),
            new Identity('uuid', 'foo'),
            Set::of('string', 'Label'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )),
            Set::of(
                ValueObject::class,
                ValueObject::of(
                    new ClassName(get_class($child)),
                    Set::of('string', 'AnotherLabel'),
                    ValueObjectRelationship::of(
                        new ClassName(get_class($rel)),
                        new RelationshipType('foo'),
                        'rel',
                        'child',
                        Map::of('string', Type::class)
                            ('created', new DateType)
                            ('empty', StringType::fromConfig(
                                (new Map('string', 'mixed'))
                                    ->put('nullable', null),
                                new Types
                            ))
                    ),
                    Map::of('string', Type::class)
                        ('content', new StringType)
                        ('empty', StringType::fromConfig(
                            (new Map('string', 'mixed'))
                                ->put('nullable', null),
                            new Types
                        ))
                )
            )
        );

        $ar = $make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            (new Map('string', 'mixed'))
                ->put('uuid', 24)
                ->put('created', '2016-01-01T00:00:00+0200')
                ->put('rel', (new Map('string', 'mixed'))
                    ->put('created', '2016-01-01T00:00:00+0200')
                    ->put('child', (new Map('string', 'mixed'))
                        ->put('content', 'foo')
                    )
                )
        );

        $this->assertInstanceOf(get_class($entity), $ar);
        $this->assertSame($identity, $ar->uuid);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->created->format('c')
        );
        $this->assertNull($ar->empty);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->rel->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->rel->created->format('c')
        );
        $this->assertNull($ar->rel->empty);
        $this->assertInstanceOf(
            get_class($child),
            $ar->rel->child
        );
        $this->assertSame('foo', $ar->rel->child->content);
        $this->assertNull($ar->rel->child->empty);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildNonAggregate()
    {
        (new AggregateFactory)(
            $this->createMock(IdentityInterface::class),
            $this->createMock(Entity::class),
            new Map('string', 'mixed')
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 3 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenTryingToBuildWithInvalidData()
    {
        (new AggregateFactory)(
            $this->createMock(IdentityInterface::class),
            Aggregate::of(
                new ClassName('foo'),
                new Identity('uuid', 'foo'),
                Set::of('string', 'Label')
            ),
            new Map('string', 'variable')
        );
    }

    public function reflection(): array
    {
        $injection = $this->createMock(InjectionStrategy::class);
        $return = null;
        $injection
            ->method('inject')
            ->with($this->callback(static function($object) use (&$return) {
                $return = $object;

                return true;
            }))
            ->will($this->returnCallback(static function() use (&$return) {
                return $return;
            }));

        return [
            [null, null],
            [
                new class implements Instanciator {
                    public function build(string $class, MapInterface $properties): object
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
                new class implements Instanciator {
                    public function build(string $class, MapInterface $properties): object
                    {
                        $object = new $class;
                        $properties->foreach(function($name, $value) use ($object) {
                            $object->$name = $value;
                        });

                        return $object;
                    }

                    public function parameters(string $class): SetInterface
                    {
                        return new Set('string');
                    }
                },
                $injection
            ],
        ];
    }
}
