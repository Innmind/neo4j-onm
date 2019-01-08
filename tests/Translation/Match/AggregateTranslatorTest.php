<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\Match\AggregateTranslator,
    Translation\MatchTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            MatchTranslator::class,
            new AggregateTranslator
        );
    }

    public function testTranslate()
    {
        $translate = new AggregateTranslator;

        $meta = Aggregate::of(
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
        $identityMatch = $translate($meta);

        $this->assertInstanceOf(IdentityMatch::class, $identityMatch);
        $this->assertSame(
            'MATCH (entity:Label) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) RETURN entity, entity_rel, entity_rel_child',
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
