<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory\RelationshipFactory,
    MetadataFactory,
    Metadata\Relationship,
    EntityFactory\RelationshipFactory as EntityFactory,
    Type\DateType,
    Types,
    Repository\Repository,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipFactoryTest extends TestCase
{
    private $make;

    public function setUp()
    {
        $this->make = new RelationshipFactory(new Types);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MetadataFactory::class,
            $this->make
        );
    }

    public function testMake()
    {
        $ar = ($this->make)((new Map('string', 'mixed'))
            ->put('class', 'SomeRelationship')
            ->put('rel_type', 'SOME_RELATIONSHIP')
            ->put('identity', [
                'property' => 'uuid',
                'type' => 'UUID',
            ])
            ->put('startNode', [
                'property' => 'startProperty',
                'type' => 'UUID',
                'target' => 'target',
            ])
            ->put('endNode', [
                'property' => 'endProperty',
                'type' => 'UUID',
                'target' => 'target',
            ])
            ->put('properties', [
                'created' => [
                    'type' => 'date',
                ],
            ])
        );

        $this->assertInstanceOf(Relationship::class, $ar);
        $this->assertSame('SomeRelationship', (string) $ar->class());
        $this->assertSame(Repository::class, (string) $ar->repository());
        $this->assertSame(EntityFactory::class, (string) $ar->factory());
        $this->assertSame('SOME_RELATIONSHIP', (string) $ar->type());
        $this->assertSame('uuid', $ar->identity()->property());
        $this->assertSame('UUID', $ar->identity()->type());
        $this->assertSame('startProperty', $ar->startNode()->property());
        $this->assertSame('UUID', $ar->startNode()->type());
        $this->assertSame('target', $ar->startNode()->target());
        $this->assertSame('endProperty', $ar->endNode()->property());
        $this->assertSame('UUID', $ar->endNode()->type());
        $this->assertSame('target', $ar->endNode()->target());
        $this->assertInstanceOf(
            DateType::class,
            $ar->properties()->get('created')->type()
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenInvalidConfigMap()
    {
        ($this->make)(new Map('string', 'variable'));
    }
}
