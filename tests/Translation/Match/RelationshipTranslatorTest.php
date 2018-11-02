<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\Match\RelationshipTranslator,
    Translation\MatchTranslator,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Types,
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
        $translator = new RelationshipTranslator;

        $meta = new Relationship(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id')
        );
        $meta = $meta
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )
            );
        $im = $translator->translate($meta);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (start)-[entity:type]->(end) RETURN start, end, entity',
            $im->query()->cypher()
        );
        $this->assertSame(0, $im->query()->parameters()->count());
        $this->assertInstanceOf(MapInterface::class, $im->variables());
        $this->assertSame(
            'string',
            (string) $im->variables()->keyType()
        );
        $this->assertSame(
            Entity::class,
            (string) $im->variables()->valueType()
        );
        $this->assertSame(1, $im->variables()->size());
        $this->assertSame($meta, $im->variables()->get('entity'));
    }
}
