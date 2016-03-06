<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory\RelationshipFactory,
    MetadataFactoryInterface,
    Metadata\Relationship,
    Type\DateType,
    Types
};
use Innmind\Immutable\Collection;

class RelationshipFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $f;

    public function setUp()
    {
        $this->f = new RelationshipFactory(new Types);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MetadataFactoryInterface::class,
            $this->f
        );
    }

    public function testMake()
    {
        $ar = $this->f->make(new Collection([
            'class' => 'SomeRelationship',
            'alias' => 'SR',
            'repository' => 'SRRepository',
            'factory' => 'SRFactory',
            'rel_type' => 'SOME_RELATIONSHIP',
            'identity' => [
                'property' => 'uuid',
                'type' => 'UUID',
            ],
            'startNode' => [
                'property' => 'startProperty',
                'type' => 'UUID',
            ],
            'endNode' => [
                'property' => 'endProperty',
                'type' => 'UUID',
            ],
            'properties' => [
                'created' => [
                    'type' => 'date',
                ],
            ],
        ]));

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
        $this->assertSame('endProperty', $ar->endNode()->property());
        $this->assertSame('UUID', $ar->endNode()->type());
        $this->assertInstanceOf(
            DateType::class,
            $ar->properties()->get('created')->type()
        );
    }
}
