<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\RelationshipTranslator,
    Translation\IdentityMatchTranslator,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity as IdentityInterface,
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
            IdentityMatchTranslator::class,
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
        $identity = $this->createMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $identityMatch = $translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $identityMatch);
        $this->assertSame(
            'MATCH (start)-[entity:type { id: {entity_identity} }]->(end) RETURN start, end, entity',
            $identityMatch->query()->cypher()
        );
        $this->assertCount(1, $identityMatch->query()->parameters());
        $this->assertSame(
            'foobar',
            $identityMatch->query()->parameters()->get('entity_identity')->value()
        );
        $this->assertInstanceOf(MapInterface::class, $identityMatch->variables());
        $this->assertSame(
            'string',
            (string) $identityMatch->variables()->keyType()
        );
        $this->assertSame(
            Entity::class,
            (string) $identityMatch->variables()->valueType()
        );
        $this->assertCount(1, $identityMatch->variables());
        $this->assertSame($meta, $identityMatch->variables()->get('entity'));
    }
}
