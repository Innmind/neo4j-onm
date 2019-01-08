<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\Match\RelationshipTranslator,
    Translation\MatchTranslator,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            MatchTranslator::class,
            new RelationshipTranslator
        );
    }

    public function testTranslate()
    {
        $translate = new RelationshipTranslator;

        $meta = Relationship::of(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
        );
        $identityMatch = $translate($meta);

        $this->assertInstanceOf(IdentityMatch::class, $identityMatch);
        $this->assertSame(
            'MATCH (start)-[entity:type]->(end) RETURN start, end, entity',
            $identityMatch->query()->cypher()
        );
        $this->assertSame(0, $identityMatch->query()->parameters()->count());
        $this->assertInstanceOf(MapInterface::class, $identityMatch->variables());
        $this->assertSame(
            'string',
            (string) $identityMatch->variables()->keyType()
        );
        $this->assertSame(
            Entity::class,
            (string) $identityMatch->variables()->valueType()
        );
        $this->assertSame(1, $identityMatch->variables()->size());
        $this->assertSame($meta, $identityMatch->variables()->get('entity'));
    }
}
