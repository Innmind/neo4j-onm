<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\Cypher\AggregateVisitor,
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Type,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Map,
    Set,
};
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
        $this->assertInstanceOf(CypherVisitor::class, $this->visitor);
    }

    public function testVisit()
    {
        $condition = ($this->visitor)(
            (new Property('created', Sign::equality(), 10))
                ->and(new Property('empty', Sign::equality(), 20))
                ->or(new Property('rel.created', Sign::equality(), 30))
                ->and(new Property('rel.empty', Sign::equality(), 40))
                ->and(new Property('rel.child.content', Sign::equality(), 50))
                ->and((new Property('rel.child.empty', Sign::equality(), 60))->not())
        );

        $this->assertSame(
            '(((((entity.created = $entity_created1 AND entity.empty = $entity_empty2) OR entity_rel.created = $entity_rel_created3) AND entity_rel.empty = $entity_rel_empty4) AND entity_rel_child.content = $entity_rel_child_content5) AND NOT (entity_rel_child.empty = $entity_rel_child_empty6))',
            $condition->cypher()
        );
        $this->assertSame('string', (string) $condition->parameters()->keyType());
        $this->assertSame('mixed', (string) $condition->parameters()->valueType());
        $this->assertCount(6, $condition->parameters());
        $this->assertSame(10, $condition->parameters()->get('entity_created1'));
        $this->assertSame(20, $condition->parameters()->get('entity_empty2'));
        $this->assertSame(30, $condition->parameters()->get('entity_rel_created3'));
        $this->assertSame(40, $condition->parameters()->get('entity_rel_empty4'));
        $this->assertSame(50, $condition->parameters()->get('entity_rel_child_content5'));
        $this->assertSame(60, $condition->parameters()->get('entity_rel_child_empty6'));
    }
}
