<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\AggregateTranslator,
    Translation\IdentityMatchTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity as IdentityInterface,
    IdentityMatch,
    Type,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            IdentityMatchTranslator::class,
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
        $identity = $this->createMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $identityMatch = $translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $identityMatch);
        $this->assertSame(
            'MATCH (entity:Label { id: $entity_identity }) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) RETURN entity, entity_rel, entity_rel_child',
            $identityMatch->query()->cypher()
        );
        $this->assertCount(1, $identityMatch->query()->parameters());
        $this->assertSame(
            'foobar',
            $identityMatch->query()->parameters()->get('entity_identity')->value()
        );
        $this->assertInstanceOf(Map::class, $identityMatch->variables());
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
