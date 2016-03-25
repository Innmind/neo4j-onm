<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation\Specification\Visitor\Cypher;

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
    Tests\Fixtures\Specification\Property
};
use Innmind\Immutable\{
    Collection,
    SequenceInterface,
    CollectionInterface
};

class RelationshipVisitorTest extends \PHPUnit_Framework_TestCase
{
    private $v;

    public function setUp()
    {
        $this->v = new RelationshipVisitor(
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
                        new Collection(['nullable' => null])
                    )
                )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(CypherVisitorInterface::class, $this->v);
    }

    public function testVisit()
    {
        $condition = $this->v->visit(
            (new Property('created', '=', 10))
                ->or(new Property('empty', '=', 20))
                ->and(new Property('start', '=', 'foo'))
                ->and((new Property('end', '=', 'bar'))->not())
        );

        $this->assertInstanceOf(SequenceInterface::class, $condition);
        $this->assertSame(2, $condition->size());
        $this->assertSame(
            '(((entity.created = {entity_created1} OR entity.empty = {entity_empty2}) AND start.id = {start_id3}) AND NOT (end.id = {end_id4}))',
            $condition->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $condition->get(1)
        );
        $this->assertSame(
            [
                'entity_created1' => 10,
                'entity_empty2' => 20,
                'start_id3' => 'foo',
                'end_id4' => 'bar',
            ],
            $condition->get(1)->toPrimitive()
        );
    }
}
