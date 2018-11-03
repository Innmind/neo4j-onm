<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Types,
    Type,
    Query\PropertiesMatch,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AggregateVisitorTest extends TestCase
{
    private $visitor;

    public function setUp()
    {
        $this->visitor = new AggregateVisitor(
            Aggregate::of(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
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
                        new ClassName('foo'),
                        Set::of('string', 'AnotherLabel'),
                        ValueObjectRelationship::of(
                            new ClassName('foo'),
                            new RelationshipType('CHILD1_OF'),
                            'rel',
                            'child'
                        )
                            ->withProperty('created', new DateType)
                            ->withProperty(
                                'empty',
                                StringType::fromConfig(
                                    (new Map('string', 'mixed'))
                                        ->put('nullable', null),
                                    new Types
                                )
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
                ->and(new Property('rel.created', '=', null))
                ->and(new Property('rel.empty', '=', null))
                ->and(new Property('rel.child.content', '=', null))
                ->and(new Property('rel.child.empty', '=', null))
        );

        $this->assertInstanceOf(MapInterface::class, $mapping);
        $this->assertSame('string', (string) $mapping->keyType());
        $this->assertSame(
            PropertiesMatch::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'entity_rel', 'entity_rel_child'],
            $mapping->keys()->toPrimitive()
        );
        $this->assertCount(2, $mapping->get('entity')->properties());
        $this->assertSame('{entity_empty}', $mapping->get('entity')->properties()->get('empty'));
        $this->assertSame('{entity_created}', $mapping->get('entity')->properties()->get('created'));
        $this->assertCount(2, $mapping->get('entity')->parameters());
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_empty'));
        $this->assertNull($mapping->get('entity')->parameters()->get('entity_created'));
        $this->assertCount(2, $mapping->get('entity_rel')->properties());
        $this->assertSame('{entity_rel_empty}', $mapping->get('entity_rel')->properties()->get('empty'));
        $this->assertSame('{entity_rel_created}', $mapping->get('entity_rel')->properties()->get('created'));
        $this->assertCount(2, $mapping->get('entity_rel')->parameters());
        $this->assertNull($mapping->get('entity_rel')->parameters()->get('entity_rel_empty'));
        $this->assertNull($mapping->get('entity_rel')->parameters()->get('entity_rel_created'));
        $this->assertCount(2, $mapping->get('entity_rel_child')->properties());
        $this->assertSame('{entity_rel_child_empty}', $mapping->get('entity_rel_child')->properties()->get('empty'));
        $this->assertSame('{entity_rel_child_content}', $mapping->get('entity_rel_child')->properties()->get('content'));
        $this->assertCount(2, $mapping->get('entity_rel_child')->parameters());
        $this->assertNull($mapping->get('entity_rel_child')->parameters()->get('entity_rel_child_empty'));
        $this->assertNull($mapping->get('entity_rel_child')->parameters()->get('entity_rel_child_content'));
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
