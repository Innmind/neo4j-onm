<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Match;

use Innmind\Neo4j\ONM\{
    Translation\Match\DelegationTranslator,
    Translation\MatchTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    IdentityMatch,
    Types,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class DelegationTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            MatchTranslator::class,
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
                ('empty', StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )),
            Set::of(
                ValueObject::class,
                ValueObject::of(
                    new ClassName('foo'),
                    Set::of('string', 'AnotherLabel'),
                    (new ValueObjectRelationship(
                        new ClassName('foo'),
                        new RelationshipType('CHILD1_OF'),
                        'rel',
                        'child'
                    ))
                        ->withProperty('created', new DateType)
                        ->withProperty(
                            'empty',
                            StringType::fromConfig(
                                (new Map('string', 'mixed'))
                                    ->put('nullable', null),
                                new Types
                            )
                        ),
                    Map::of('string', Type::class)
                        ('content', new StringType)
                        ('empty', StringType::fromConfig(
                            (new Map('string', 'mixed'))
                                ->put('nullable', null),
                            new Types
                        ))
                )
            )
        );
        $im = $translate($meta);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (entity:Label) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) RETURN entity, entity_rel, entity_rel_child',
            $im->query()->cypher()
        );
        $this->assertCount(0, $im->query()->parameters());
        $this->assertInstanceOf(MapInterface::class, $im->variables());
        $this->assertSame(
            'string',
            (string) $im->variables()->keyType()
        );
        $this->assertSame(
            Entity::class,
            (string) $im->variables()->valueType()
        );
        $this->assertCount(1, $im->variables());
        $this->assertSame($meta, $im->variables()->get('entity'));
    }

    public function testTranslateRelationship()
    {
        $translate = new DelegationTranslator;
        $meta = new Relationship(
            new ClassName('foo'),
            new Identity('id', 'foo'),
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
        $im = $translate($meta);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (start)-[entity:type]->(end) RETURN start, end, entity',
            $im->query()->cypher()
        );
        $this->assertCount(0, $im->query()->parameters());
        $this->assertInstanceOf(MapInterface::class, $im->variables());
        $this->assertSame(
            'string',
            (string) $im->variables()->keyType()
        );
        $this->assertSame(
            Entity::class,
            (string) $im->variables()->valueType()
        );
        $this->assertCount(1, $im->variables());
        $this->assertSame($meta, $im->variables()->get('entity'));
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\Neo4j\ONM\Translation\MatchTranslator>
     */
    public function testThrowWhenGivingInvalidTranslatorsMap()
    {
        new DelegationTranslator(new Map('int', 'int'));
    }
}
