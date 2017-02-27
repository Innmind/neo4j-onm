<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory\RelationshipFactory,
    MetadataFactoryInterface,
    Metadata\Relationship,
    Type\DateType,
    Types
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipFactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new RelationshipFactory(new Types);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MetadataFactoryInterface::class,
            $this->factory
        );
    }

    public function testMake()
    {
        $ar = $this->factory->make((new Map('string', 'mixed'))
            ->put('class', 'SomeRelationship')
            ->put('alias', 'SR')
            ->put('repository', 'SRRepository')
            ->put('factory', 'SRFactory')
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
        $this->assertSame('SR', (string) $ar->alias());
        $this->assertSame('SRRepository', (string) $ar->repository());
        $this->assertSame('SRFactory', (string) $ar->factory());
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
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidConfigMap()
    {
        $this->factory->make(new Map('string', 'variable'));
    }
}
