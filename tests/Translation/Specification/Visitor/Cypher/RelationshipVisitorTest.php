<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\Cypher\RelationshipVisitor,
    Translation\Specification\Visitor\CypherVisitorInterface,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Types
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    Map,
    SequenceInterface,
    MapInterface
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
        $this->assertInstanceOf(CypherVisitorInterface::class, $this->visitor);
    }

    public function testVisit()
    {
        $condition = $this->visitor->visit(
            (new Property('created', '=', 10))
                ->or(new Property('empty', '=', 20))
                ->and(new Property('start', '=', 'foo'))
                ->and((new Property('end', '=', 'bar'))->not())
        );

        $this->assertInstanceOf(SequenceInterface::class, $condition);
        $this->assertCount(2, $condition);
        $this->assertSame(
            '(((entity.created = {entity_created1} OR entity.empty = {entity_empty2}) AND start.id = {start_id3}) AND NOT (end.id = {end_id4}))',
            $condition->first()
        );
        $this->assertInstanceOf(
            MapInterface::class,
            $condition->last()
        );
        $this->assertSame('string', (string) $condition->last()->keyType());
        $this->assertSame('mixed', (string) $condition->last()->valueType());
        $this->assertSame(10, $condition->last()->get('entity_created1'));
        $this->assertSame(20, $condition->last()->get('entity_empty2'));
        $this->assertSame('foo', $condition->last()->get('start_id3'));
        $this->assertSame('bar', $condition->last()->get('end_id4'));
    }
}
