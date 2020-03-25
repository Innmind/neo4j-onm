<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitor,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Type,
    Query\PropertiesMatch,
    Exception\SpecificationNotApplicableAsPropertyMatch,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class AggregateVisitorTest extends TestCase
{
    private $visitor;

    public function setUp(): void
    {
        $this->visitor = new AggregateVisitor(
            Aggregate::of(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
                Set::of('string', 'Label'),
                Map::of('string', Type::class)
                    ('created', new DateType)
                    ('empty', StringType::nullable()),
                Set::of(
                    Child::class,
                    Child::of(
                        new ClassName('foo'),
                        Set::of('string', 'AnotherLabel'),
                        Child\Relationship::of(
                            new ClassName('foo'),
                            new RelationshipType('CHILD1_OF'),
                            'rel',
                            'child',
                            Map::of('string', Type::class)
                                ('created', new DateType)
                                ('empty', StringType::nullable())
                        ),
                        Map::of('string', Type::class)
                            ('content', new StringType)
                            ('empty', StringType::nullable())
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
            (new Property('created', Sign::equality(), null))
                ->and(new Property('empty', Sign::equality(), null))
                ->and(new Property('rel.created', Sign::equality(), null))
                ->and(new Property('rel.empty', Sign::equality(), null))
                ->and(new Property('rel.child.content', Sign::equality(), null))
                ->and(new Property('rel.child.empty', Sign::equality(), null))
        );

        $this->assertInstanceOf(Map::class, $mapping);
        $this->assertSame('string', (string) $mapping->keyType());
        $this->assertSame(
            PropertiesMatch::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'entity_rel', 'entity_rel_child'],
            unwrap($mapping->keys()),
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
