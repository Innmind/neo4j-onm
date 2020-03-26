<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\RelationshipVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Type,
    Query\PropertiesMatch,
    Exception\SpecificationNotApplicableAsPropertyMatch,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Specification\Sign;
use Innmind\Immutable\Map;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class RelationshipVisitorTest extends TestCase
{
    private $visitor;

    public function setUp(): void
    {
        $this->visitor = new RelationshipVisitor(
            Relationship::of(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id'),
                Map::of('string', Type::class)
                    ('created', new DateType)
                    ('empty', StringType::nullable())
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
            (new Property('created', Sign::equality(), null))
                ->and(new Property('empty', Sign::equality(), null))
                ->and(new Property('start', Sign::equality(), 'foo'))
                ->and(new Property('end', Sign::equality(), 'bar'))
        );

        $this->assertInstanceOf(Map::class, $mapping);
        $this->assertSame('string', (string) $mapping->keyType());
        $this->assertSame(
            PropertiesMatch::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'start', 'end'],
            unwrap($mapping->keys()),
        );
        $this->assertSame('$entity_empty', $mapping->get('entity')->properties()->get('empty'));
        $this->assertSame('$entity_created', $mapping->get('entity')->properties()->get('created'));
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_empty'));
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_created'));
        $this->assertCount(1, $mapping->get('start')->properties());
        $this->assertSame('$start_id', $mapping->get('start')->properties()->get('id'));
        $this->assertCount(1, $mapping->get('start')->parameters());
        $this->assertSame('foo', $mapping->get('start')->parameters()->get('start_id'));
        $this->assertCount(1, $mapping->get('end')->properties());
        $this->assertSame('$end_id', $mapping->get('end')->properties()->get('id'));
        $this->assertCount(1, $mapping->get('end')->parameters());
        $this->assertSame('bar', $mapping->get('end')->parameters()->get('end_id'));
    }

    public function testThrowWhenNotDirectComparison()
    {
        $this->expectException(SpecificationNotApplicableAsPropertyMatch::class);

        ($this->visitor)(new Property('created', Sign::contains(), 'foo'));
    }

    public function testThrowWhenOrOperator()
    {
        $this->expectException(SpecificationNotApplicableAsPropertyMatch::class);

        ($this->visitor)(
            (new Property('created', Sign::equality(), 'foo'))
                ->or(new Property('empty', Sign::equality(), 'foo'))
        );
    }

    public function testThrowWhenNegatedSpecification()
    {
        $this->expectException(SpecificationNotApplicableAsPropertyMatch::class);

        ($this->visitor)(
            (new Property('created', Sign::equality(), 'foo'))->not()
        );
    }
}
