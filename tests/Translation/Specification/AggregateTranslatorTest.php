<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\AggregateTranslator,
    Translation\SpecificationTranslatorInterface,
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
    IdentityMatch
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query\Parameter;
use Innmind\Immutable\Collection;
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    private $meta;

    public function setUp()
    {
        $this->meta = (new Aggregate(
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
            );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationTranslatorInterface::class,
            new AggregateTranslator
        );
    }

    public function testTranslateWithPropertyMatch()
    {
        $t = new AggregateTranslator;

        $match = $t->translate(
            $this->meta,
            (new Property('created', '=', 10))
                ->and(new Property('empty', '=', 20))
                ->and(new Property('rel.created', '=', 30))
                ->and(new Property('rel.empty', '=', 40))
                ->and(new Property('rel.child.content', '=', 50))
                ->and(new Property('rel.child.empty', '=', 60))
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (entity:Label { empty: {entity_empty}, created: {entity_created} }) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF { empty: {entity_rel_empty}, created: {entity_rel_created} }]-(entity_rel_child:AnotherLabel { empty: {entity_rel_child_empty}, content: {entity_rel_child_content} }) RETURN entity, entity_rel, entity_rel_child',
            $match->query()->cypher()
        );
        $this->assertSame(6, $match->query()->parameters()->count());
        $match
            ->query()
            ->parameters()
            ->each(function(int $idx, Parameter $parameter) {
                $expected = [
                    'entity_empty' => 20,
                    'entity_created' => 10,
                    'entity_rel_empty' => 40,
                    'entity_rel_created' => 30,
                    'entity_rel_child_empty' => 60,
                    'entity_rel_child_content' => 50,
                ];

                $this->assertTrue(isset($expected[$parameter->key()]));
                $this->assertSame(
                    $expected[$parameter->key()],
                    $parameter->value()
                );
            });
        $this->assertSame(1, $match->variables()->size());
        $this->assertSame($this->meta, $match->variables()->get('entity'));
    }

    public function testTranslateWithWhereClause()
    {
        $t = new AggregateTranslator;

        $match = $t->translate(
            $this->meta,
            (new Property('created', '=', 10))
                ->and(new Property('empty', '=', 20))
                ->or(new Property('rel.created', '=', 30))
                ->and(new Property('rel.empty', '=', 40))
                ->and(new Property('rel.child.content', '=', 50))
                ->and((new Property('rel.child.empty', '=', 60))->not())
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (entity:Label) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) WHERE (((((entity.created = {entity_created1} AND entity.empty = {entity_empty2}) OR entity_rel.created = {entity_rel_created3}) AND entity_rel.empty = {entity_rel_empty4}) AND entity_rel_child.content = {entity_rel_child_content5}) AND NOT (entity_rel_child.empty = {entity_rel_child_empty6})) RETURN entity, entity_rel, entity_rel_child',
            $match->query()->cypher()
        );
        $this->assertSame(6, $match->query()->parameters()->count());
        $match
            ->query()
            ->parameters()
            ->each(function(int $idx, Parameter $parameter) {
                $expected = [
                    'entity_empty2' => 20,
                    'entity_created1' => 10,
                    'entity_rel_empty4' => 40,
                    'entity_rel_created3' => 30,
                    'entity_rel_child_empty6' => 60,
                    'entity_rel_child_content5' => 50,
                ];

                $this->assertTrue(isset($expected[$parameter->key()]));
                $this->assertSame(
                    $expected[$parameter->key()],
                    $parameter->value()
                );
            });
        $this->assertSame(1, $match->variables()->size());
        $this->assertSame($this->meta, $match->variables()->get('entity'));
    }
}
