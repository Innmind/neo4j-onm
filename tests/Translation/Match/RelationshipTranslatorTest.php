<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\Match\RelationshipTranslator,
    Translation\MatchTranslatorInterface,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Types
};
use Innmind\Immutable\{
    Map,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $t = new RelationshipTranslator;
        $this->assertInstanceOf(
            MatchTranslatorInterface::class,
            $t
        );

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
        $im = $t->translate($meta);

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
            EntityInterface::class,
            (string) $im->variables()->valueType()
        );
        $this->assertSame(1, $im->variables()->size());
        $this->assertSame($meta, $im->variables()->get('entity'));
    }
}
