<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\RelationshipTranslator,
    Translation\SpecificationTranslator,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Identity\Uuid,
    Type,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query\Parameter;
use Innmind\Specification\Sign;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    private $meta;

    public function setUp(): void
    {
        $this->meta = Relationship::of(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
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
        $translate = new RelationshipTranslator;

        $match = $translate(
            $this->meta,
            (new Property('created', Sign::equality(), 1))
                ->and(new Property('empty', Sign::equality(), 2))
                ->and(new Property('start', Sign::equality(), 'foo'))
                ->and(new Property('end', Sign::equality(), new Uuid('11111111-1111-1111-1111-111111111111')))
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (start { id: $start_id })-[entity:type { empty: $entity_empty, created: $entity_created }]->(end { id: $end_id }) RETURN start, end, entity',
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
        $translate = new RelationshipTranslator;

        $match = $translate(
            $this->meta,
            (new Property('created', Sign::equality(), 10))
                ->or(new Property('empty', Sign::equality(), 20))
                ->and(new Property('start', Sign::equality(), 'foo'))
                ->and((new Property('end', Sign::equality(), new Uuid('11111111-1111-1111-1111-111111111111')))->not())
        );

        $this->assertInstanceOf(IdentityMatch::class, $match);
        $this->assertSame(
            'MATCH (start)-[entity:type]->(end) WHERE (((entity.created = $entity_created1 OR entity.empty = $entity_empty2) AND start.id = $start_id3) AND NOT (end.id = $end_id4)) RETURN start, end, entity',
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
