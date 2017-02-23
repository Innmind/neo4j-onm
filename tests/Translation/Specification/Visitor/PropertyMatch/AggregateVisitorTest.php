<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification\Visitor\PropertyMatch;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Visitor\PropertyMatch\AggregateVisitor,
    Translation\Specification\Visitor\PropertyMatchVisitorInterface,
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
    MapInterface,
    SequenceInterface,
    CollectionInterface
};
use PHPUnit\Framework\TestCase;

class AggregateVisitorTest extends TestCase
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
        $this->assertInstanceOf(PropertyMatchVisitorInterface::class, $this->v);
    }

    public function testVisit()
    {
        $mapping = $this->v->visit(
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
            SequenceInterface::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'entity_rel', 'entity_rel_child'],
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
        $this->assertSame(2, $mapping->get('entity_rel')->size());
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity_rel')->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity_rel')->get(1)
        );
        $this->assertSame(
            [
                'empty' => '{entity_rel_empty}',
                'created' => '{entity_rel_created}',
            ],
            $mapping->get('entity_rel')->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                'entity_rel_empty' => null,
                'entity_rel_created' => null,
            ],
            $mapping->get('entity_rel')->get(1)->toPrimitive()
        );
        $this->assertSame(2, $mapping->get('entity_rel_child')->size());
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity_rel_child')->get(0)
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $mapping->get('entity_rel_child')->get(1)
        );
        $this->assertSame(
            [
                'empty' => '{entity_rel_child_empty}',
                'content' => '{entity_rel_child_content}',
            ],
            $mapping->get('entity_rel_child')->get(0)->toPrimitive()
        );
        $this->assertSame(
            [
                'entity_rel_child_empty' => null,
                'entity_rel_child_content' => null,
            ],
            $mapping->get('entity_rel_child')->get(1)->toPrimitive()
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
