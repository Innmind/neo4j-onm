<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation\Specification\Visitor\PropertyMatch;

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
    Tests\Fixtures\Specification\Property
};
use Innmind\Immutable\{
    Collection,
    MapInterface,
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
        $this->assertInstanceOf(PropertyMatchVisitorInterface::class, $this->v);
    }

    public function testVisit()
    {
        $mapping = $this->v->visit(
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
        $this->assertSame(2, $mapping->get('entity')->size());
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity')->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity')->get(1)
        );
        $this->assertSame(
            [
                'empty' => '{entity_empty}',
                'created' => '{entity_created}',
            ],
            $mapping->get('entity')->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                'entity_empty' => null,
                'entity_created' => null,
            ],
            $mapping->get('entity')->get(1)->toPrimitive()
        );
        $this->assertSame(2, $mapping->get('start')->size());
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('start')->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('start')->get(1)
        );
        $this->assertSame(
            [
                'id' => '{start_id}',
            ],
            $mapping->get('start')->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                'start_id' => 'foo',
            ],
            $mapping->get('start')->get(1)->toPrimitive()
        );
        $this->assertSame(2, $mapping->get('end')->size());
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('end')->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('end')->get(1)
        );
        $this->assertSame(
            [
                'id' => '{end_id}',
            ],
            $mapping->get('end')->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                'end_id' => 'bar',
            ],
            $mapping->get('end')->get(1)->toPrimitive()
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNotDirectComparison()
    {
        $this->v->visit(new Property('created', '~=', 'foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenOrOperator()
    {
        $this->v->visit(
            (new Property('created', '=', 'foo'))
                ->or(new Property('empty', '=', 'foo'))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNegatedSpecification()
    {
        $this->v->visit(
            (new Property('created', '=', 'foo'))->not()
        );
    }
}
