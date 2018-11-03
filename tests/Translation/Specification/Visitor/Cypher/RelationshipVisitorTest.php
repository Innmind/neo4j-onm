<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\Cypher\RelationshipVisitor,
    Translation\Specification\Visitor\CypherVisitor,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Type,
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
        $this->assertInstanceOf(CypherVisitor::class, $this->visitor);
    }

    public function testVisit()
    {
        $condition = ($this->visitor)(
            (new Property('created', '=', 10))
                ->or(new Property('empty', '=', 20))
                ->and(new Property('start', '=', 'foo'))
                ->and((new Property('end', '=', 'bar'))->not())
        );

        $this->assertSame(
            '(((entity.created = {entity_created1} OR entity.empty = {entity_empty2}) AND start.id = {start_id3}) AND NOT (end.id = {end_id4}))',
            $condition->cypher()
        );
        $this->assertSame('string', (string) $condition->parameters()->keyType());
        $this->assertSame('mixed', (string) $condition->parameters()->valueType());
        $this->assertSame(10, $condition->parameters()->get('entity_created1'));
        $this->assertSame(20, $condition->parameters()->get('entity_empty2'));
        $this->assertSame('foo', $condition->parameters()->get('start_id3'));
        $this->assertSame('bar', $condition->parameters()->get('end_id4'));
    }
}
