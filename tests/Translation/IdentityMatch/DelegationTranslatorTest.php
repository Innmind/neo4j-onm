<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\DelegationTranslator,
    Translation\IdentityMatchTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
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
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class DelegationTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            IdentityMatchTranslator::class,
            new DelegationTranslator
        );
    }

    public function testTranslateAggregate()
    {
        $translate = new DelegationTranslator;
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

    public function testTranslateRelationship()
    {
        $translate = new DelegationTranslator;
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
            'MATCH (start)-[entity:type { id: $entity_identity }]->(end) RETURN start, end, entity',
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

    public function testThrowWhenGivingInvalidTranslatorsMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Neo4j\ONM\Translation\IdentityMatchTranslator>');

        new DelegationTranslator(Map::of('int', 'int'));
    }
}
