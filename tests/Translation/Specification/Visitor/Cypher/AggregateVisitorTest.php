<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\Cypher;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\Cypher\AggregateVisitor,
    Translation\Specification\Visitor\CypherVisitorInterface,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    Collection,
    SequenceInterface,
    CollectionInterface
};

class AggregateVisitorTest extends \PHPUnit_Framework_TestCase
{
    private $v;

    public function setUp()
    {
        $this->v = new AggregateVisitor(
            (new Aggregate(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new Alias('foo'),
                ['Label']
            ))
                ->withProperty('created', new DateType)
                ->withProperty(
                    'empty',
                    StringType::fromConfig(
                        new Collection(['nullable' => null])
                    )
                )
                ->withChild(
                    (new ValueObject(
                        new ClassName('foo'),
                        ['AnotherLabel'],
                        (new ValueObjectRelationship(
                            new ClassName('foo'),
                            new RelationshipType('CHILD1_OF'),
                            'rel',
                            'child'
                        ))
                            ->withProperty('created', new DateType)
                            ->withProperty(
                                'empty',
                                StringType::fromConfig(
                                    new Collection(['nullable' => null])
                                )
                            )
                    ))
                        ->withProperty('content', new StringType)
                        ->withProperty(
                            'empty',
                            StringType::fromConfig(
                                new Collection(['nullable' => null])
                            )
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
                ->and(new Property('empty', '=', 20))
                ->or(new Property('rel.created', '=', 30))
                ->and(new Property('rel.empty', '=', 40))
                ->and(new Property('rel.child.content', '=', 50))
                ->and((new Property('rel.child.empty', '=', 60))->not())
        );

        $this->assertInstanceOf(SequenceInterface::class, $condition);
        $this->assertSame(2, $condition->size());
        $this->assertSame(
            '(((((entity.created = {entity_created1} AND entity.empty = {entity_empty2}) OR entity_rel.created = {entity_rel_created3}) AND entity_rel.empty = {entity_rel_empty4}) AND entity_rel_child.content = {entity_rel_child_content5}) AND NOT (entity_rel_child.empty = {entity_rel_child_empty6}))',
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
                'entity_rel_created3' => 30,
                'entity_rel_empty4' => 40,
                'entity_rel_child_content5' => 50,
                'entity_rel_child_empty6' => 60,
            ],
            $condition->get(1)->toPrimitive()
        );
    }
}
