<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\RelationshipTranslator,
    Translation\IdentityMatchTranslatorInterface,
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
    IdentityInterface,
    IdentityMatch
};
use Innmind\Immutable\{
    Collection,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $t = new RelationshipTranslator;
        $this->assertInstanceOf(
            IdentityMatchTranslatorInterface::class,
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
                    new Collection(['nullable' => null])
                )
            );
        $identity = $this->createMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $im = $t->translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (start)-[entity:type { id: {entity_identity} }]->(end) RETURN start, end, entity',
            $im->query()->cypher()
        );
        $this->assertSame(1, $im->query()->parameters()->count());
        $this->assertSame(
            'entity_identity',
            $im->query()->parameters()->get(0)->key()
        );
        $this->assertSame(
            'foobar',
            $im->query()->parameters()->get(0)->value()
        );
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
