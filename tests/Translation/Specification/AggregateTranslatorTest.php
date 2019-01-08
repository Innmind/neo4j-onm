<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\AggregateTranslator,
    Translation\SpecificationTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Type,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query\Parameter;
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    private $meta;

    public function setUp()
    {
        $this->meta = Aggregate::of(
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
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationTranslator::class,
            new AggregateTranslator
        );
    }

    public function testTranslateWithPropertyMatch()
    {
        $translate = new AggregateTranslator;

        $match = $translate(
            $this->meta,
            (new Property('created', Sign::equality(), 10))
                ->and(new Property('empty', Sign::equality(), 20))
                ->and(new Property('rel.created', Sign::equality(), 30))
                ->and(new Property('rel.empty', Sign::equality(), 40))
                ->and(new Property('rel.child.content', Sign::equality(), 50))
                ->and(new Property('rel.child.empty', Sign::equality(), 60))
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (entity:Label { empty: {entity_empty}, created: {entity_created} }) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF { empty: {entity_rel_empty}, created: {entity_rel_created} }]-(entity_rel_child:AnotherLabel { empty: {entity_rel_child_empty}, content: {entity_rel_child_content} }) RETURN entity, entity_rel, entity_rel_child',
            $match->query()->cypher()
        );
        $this->assertCount(6, $match->query()->parameters());
        $match
            ->query()
            ->parameters()
            ->foreach(function(string $key, Parameter $parameter) {
                $expected = [
                    'entity_empty' => 20,
                    'entity_created' => 10,
                    'entity_rel_empty' => 40,
                    'entity_rel_created' => 30,
                    'entity_rel_child_empty' => 60,
                    'entity_rel_child_content' => 50,
                ];

                $this->assertTrue(isset($expected[$key]));
                $this->assertSame(
                    $expected[$key],
                    $parameter->value()
                );
            });
        $this->assertCount(1, $match->variables());
        $this->assertSame($this->meta, $match->variables()->get('entity'));
    }

    public function testTranslateWithWhereClause()
    {
        $translate = new AggregateTranslator;

        $match = $translate(
            $this->meta,
            (new Property('created', Sign::equality(), 10))
                ->and(new Property('empty', Sign::equality(), 20))
                ->or(new Property('rel.created', Sign::equality(), 30))
                ->and(new Property('rel.empty', Sign::equality(), 40))
                ->and(new Property('rel.child.content', Sign::equality(), 50))
                ->and((new Property('rel.child.empty', Sign::equality(), 60))->not())
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (entity:Label) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) WHERE (((((entity.created = {entity_created1} AND entity.empty = {entity_empty2}) OR entity_rel.created = {entity_rel_created3}) AND entity_rel.empty = {entity_rel_empty4}) AND entity_rel_child.content = {entity_rel_child_content5}) AND NOT (entity_rel_child.empty = {entity_rel_child_empty6})) RETURN entity, entity_rel, entity_rel_child',
            $match->query()->cypher()
        );
        $this->assertCount(6, $match->query()->parameters());
        $match
            ->query()
            ->parameters()
            ->foreach(function(string $key, Parameter $parameter) {
                $expected = [
                    'entity_empty2' => 20,
                    'entity_created1' => 10,
                    'entity_rel_empty4' => 40,
                    'entity_rel_created3' => 30,
                    'entity_rel_child_empty6' => 60,
                    'entity_rel_child_content5' => 50,
                ];

                $this->assertTrue(isset($expected[$key]));
                $this->assertSame(
                    $expected[$key],
                    $parameter->value()
                );
            });
        $this->assertCount(1, $match->variables());
        $this->assertSame($this->meta, $match->variables()->get('entity'));
    }
}
