<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\RelationshipTranslator,
    Translation\SpecificationTranslator,
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
    IdentityMatch,
    Identity\Uuid,
    Types,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query\Parameter;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    private $meta;

    public function setUp()
    {
        $this->meta = (new Relationship(
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
                );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationTranslator::class,
            new RelationshipTranslator
        );
    }

    public function testTranslateWithPropertyMatch()
    {
        $translator = new RelationshipTranslator;

        $match = $translator->translate(
            $this->meta,
            (new Property('created', '=', 1))
                ->and(new Property('empty', '=', 2))
                ->and(new Property('start', '=', 'foo'))
                ->and(new Property('end', '=', new Uuid('11111111-1111-1111-1111-111111111111')))
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (start { id: {start_id} })-[entity:type { empty: {entity_empty}, created: {entity_created} }]->(end { id: {end_id} }) RETURN start, end, entity',
            $match->query()->cypher()
        );
        $this->assertCount(4, $match->query()->parameters());
        $match
            ->query()
            ->parameters()
            ->foreach(function(string $key, Parameter $parameter) {
                $expected = [
                    'entity_empty' => 2,
                    'entity_created' => 1,
                    'start_id' => 'foo',
                    'end_id' => '11111111-1111-1111-1111-111111111111',
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
        $translator = new RelationshipTranslator;

        $match = $translator->translate(
            $this->meta,
            (new Property('created', '=', 10))
                ->or(new Property('empty', '=', 20))
                ->and(new Property('start', '=', 'foo'))
                ->and((new Property('end', '=', new Uuid('11111111-1111-1111-1111-111111111111')))->not())
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (start)-[entity:type]->(end) WHERE (((entity.created = {entity_created1} OR entity.empty = {entity_empty2}) AND start.id = {start_id3}) AND NOT (end.id = {end_id4})) RETURN start, end, entity',
            $match->query()->cypher()
        );
        $this->assertCount(4, $match->query()->parameters());
        $match
            ->query()
            ->parameters()
            ->foreach(function(string $key, Parameter $parameter) {
                $expected = [
                    'entity_empty2' => 20,
                    'entity_created1' => 10,
                    'start_id3' => 'foo',
                    'end_id4' => '11111111-1111-1111-1111-111111111111',
                ];

                $this->assertTrue(isset($expected[$key]));
                $this->assertSame(
                    $expected[$key],
                    $parameter->value()
                );
            });
        $this->assertSame(1, $match->variables()->size());
        $this->assertSame($this->meta, $match->variables()->get('entity'));
    }
}
