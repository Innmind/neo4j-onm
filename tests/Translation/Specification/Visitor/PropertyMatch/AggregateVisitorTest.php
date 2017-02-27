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
    Type\StringType,
    Types
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\{
    Map,
    MapInterface,
    SequenceInterface
};
use PHPUnit\Framework\TestCase;

class AggregateVisitorTest extends TestCase
{
    private $visitor;

    public function setUp()
    {
        $this->visitor = new AggregateVisitor(
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
                        (new Map('string', 'mixed'))
                            ->put('nullable', null),
                        new Types
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
                                    (new Map('string', 'mixed'))
                                        ->put('nullable', null),
                                    new Types
                                )
                            )
                    ))
                        ->withProperty('content', new StringType)
                        ->withProperty(
                            'empty',
                            StringType::fromConfig(
                                (new Map('string', 'mixed'))
                                    ->put('nullable', null),
                                new Types
                            )
                        )
                )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(PropertyMatchVisitorInterface::class, $this->visitor);
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
            SequenceInterface::class,
            (string) $mapping->valueType()
        );
        $this->assertSame(
            ['entity', 'entity_rel', 'entity_rel_child'],
            $mapping->keys()->toPrimitive()
        );
        $this->assertCount(2, $mapping->get('entity'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity')->first()
        );
        $this->assertSame('string', (string) $mapping->get('entity')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('entity')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity')->last()
        );
        $this->assertSame('string', (string) $mapping->get('entity')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('entity')->last()->valueType());
        $this->assertCount(2, $mapping->get('entity')->first());
        $this->assertSame('{entity_empty}', $mapping->get('entity')->first()->get('empty'));
        $this->assertSame('{entity_created}', $mapping->get('entity')->first()->get('created'));
        $this->assertCount(2, $mapping->get('entity')->last());
        $this->assertNull($mapping->get('entity')->last()->get('entity_empty'));
        $this->assertNull($mapping->get('entity')->last()->get('entity_created'));
        $this->assertCount(2, $mapping->get('entity_rel'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity_rel')->first()
        );
        $this->assertSame('string', (string) $mapping->get('entity_rel')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('entity_rel')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity_rel')->last()
        );
        $this->assertSame('string', (string) $mapping->get('entity_rel')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('entity_rel')->last()->valueType());
        $this->assertCount(2, $mapping->get('entity_rel')->first());
        $this->assertSame('{entity_rel_empty}', $mapping->get('entity_rel')->first()->get('empty'));
        $this->assertSame('{entity_rel_created}', $mapping->get('entity_rel')->first()->get('created'));
        $this->assertCount(2, $mapping->get('entity_rel')->last());
        $this->assertNull($mapping->get('entity_rel')->last()->get('entity_rel_empty'));
        $this->assertNull($mapping->get('entity_rel')->last()->get('entity_rel_created'));
        $this->assertCount(2, $mapping->get('entity_rel_child'));
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity_rel_child')->first()
        );
        $this->assertSame('string', (string) $mapping->get('entity_rel_child')->first()->keyType());
        $this->assertSame('string', (string) $mapping->get('entity_rel_child')->first()->valueType());
        $this->assertInstanceOf(
            MapInterface::class,
            $mapping->get('entity_rel_child')->last()
        );
        $this->assertSame('string', (string) $mapping->get('entity_rel_child')->last()->keyType());
        $this->assertSame('mixed', (string) $mapping->get('entity_rel_child')->last()->valueType());
        $this->assertCount(2, $mapping->get('entity_rel_child')->first());
        $this->assertSame('{entity_rel_child_empty}', $mapping->get('entity_rel_child')->first()->get('empty'));
        $this->assertSame('{entity_rel_child_content}', $mapping->get('entity_rel_child')->first()->get('content'));
        $this->assertCount(2, $mapping->get('entity_rel_child')->last());
        $this->assertNull($mapping->get('entity_rel_child')->last()->get('entity_rel_child_empty'));
        $this->assertNull($mapping->get('entity_rel_child')->last()->get('entity_rel_child_content'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNotDirectComparison()
    {
        ($this->visitor)(new Property('created', '~=', 'foo'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenOrOperator()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))
                ->or(new Property('empty', '=', 'foo'))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableAsPropertyMatchException
     */
    public function testThrowWhenNegatedSpecification()
    {
        ($this->visitor)(
            (new Property('created', '=', 'foo'))->not()
        );
    }
}
