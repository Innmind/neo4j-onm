<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\RelationshipVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Types,
    Query\PropertiesMatch,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    MapInterface,
    Map,
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
        $this->assertInstanceOf(PropertyMatchVisitor::class, $this->visitor);
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
            PropertiesMatch::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'start', 'end'],
            $mapping->keys()->toPrimitive()
        );
        $this->assertSame('{entity_empty}', $mapping->get('entity')->properties()->get('empty'));
        $this->assertSame('{entity_created}', $mapping->get('entity')->properties()->get('created'));
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_empty'));
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_created'));
        $this->assertCount(1, $mapping->get('start')->properties());
        $this->assertSame('{start_id}', $mapping->get('start')->properties()->get('id'));
        $this->assertCount(1, $mapping->get('start')->parameters());
        $this->assertSame('foo', $mapping->get('start')->parameters()->get('start_id'));
        $this->assertCount(1, $mapping->get('end')->properties());
        $this->assertSame('{end_id}', $mapping->get('end')->properties()->get('id'));
        $this->assertCount(1, $mapping->get('end')->parameters());
        $this->assertSame('bar', $mapping->get('end')->parameters()->get('end_id'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatch
     */
    public function testThrowWhenNotDirectComparison()
    {
        ($this->visitor)(new Property('created', '~=', 'foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatch
     */
    public function testThrowWhenOrOperator()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))
                ->or(new Property('empty', '=', 'foo'))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatch
     */
    public function testThrowWhenNegatedSpecification()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))->not()
        );
    }
}
