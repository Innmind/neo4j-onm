<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\RelationshipVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitorInterface,
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
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Types
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    Map,
    MapInterface,
    SequenceInterface
};
use PHPUnit\Framework\TestCase;

class RelationshipVisitorTest extends TestCase
{
    private $visitor;

    public function setUp()
    {
        $this->visitor = new RelationshipVisitor(
            (new Relationship(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new Alias('foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id')
            ))
                ->withProperty('created', new DateType)
                ->withProperty(
                    'empty',
                    StringType::fromConfig(
                        (new Map('string', 'mixed'))
                            ->put('nullable', null),
                        new Types
                    )
                )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(PropertyMatchVisitorInterface::class, $this->visitor);
    }

    public function testVisit()
    {
        $mapping = ($this->visitor)(
            (new Property('created', '=', null))
                ->and(new Property('empty', '=', null))
                ->and(new Property('start', '=', 'foo'))
                ->and(new Property('end', '=', 'bar'))
        );

        $this->assertInstanceOf(MapInterface::class, $mapping);
        $this->assertSame('string', (string) $mapping->keyType());
        $this->assertSame(
            SequenceInterface::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'start', 'end'],
            $mapping->keys()->toPrimitive()
        );
        $this->assertCount(2, $mapping->get('entity'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity')->first()
        );
        $this->assertSame('string', (string) $mapping->get('entity')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('entity')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity')->last()
        );
        $this->assertSame('string', (string) $mapping->get('start')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('start')->last()->valueType());
        $this->assertSame('{entity_empty}', $mapping->get('entity')->first()->get('empty'));
        $this->assertSame('{entity_created}', $mapping->get('entity')->first()->get('created'));
        $this->assertNull($mapping->get('entity')->last()->get('entity_empty'));
        $this->assertNull($mapping->get('entity')->last()->get('entity_created'));
        $this->assertCount(2, $mapping->get('start'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('start')->first()
        );
        $this->assertSame('string', (string) $mapping->get('start')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('start')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('start')->last()
        );
        $this->assertSame('string', (string) $mapping->get('start')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('start')->last()->valueType());
        $this->assertCount(1, $mapping->get('start')->first());
        $this->assertSame('{start_id}', $mapping->get('start')->first()->get('id'));
        $this->assertCount(1, $mapping->get('start')->last());
        $this->assertSame('foo', $mapping->get('start')->last()->get('start_id'));
        $this->assertCount(2, $mapping->get('end'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('end')->first()
        );
        $this->assertSame('string', (string) $mapping->get('end')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('end')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('end')->last()
        );
        $this->assertSame('string', (string) $mapping->get('end')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('end')->last()->valueType());
        $this->assertCount(1, $mapping->get('end')->first());
        $this->assertSame('{end_id}', $mapping->get('end')->first()->get('id'));
        $this->assertCount(1, $mapping->get('end')->last());
        $this->assertSame('bar', $mapping->get('end')->last()->get('end_id'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNotDirectComparison()
    {
        ($this->visitor)(new Property('created', '~=', 'foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenOrOperator()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))
                ->or(new Property('empty', '=', 'foo'))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNegatedSpecification()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))->not()
        );
    }
}
