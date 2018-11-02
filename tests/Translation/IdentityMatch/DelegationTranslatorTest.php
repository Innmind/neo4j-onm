<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\IdentityMatch;

use Innmind\Neo4j\ONM\{
    Translation\IdentityMatch\DelegationTranslator,
    Translation\IdentityMatchTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity as IdentityInterface,
    IdentityMatch,
    Types,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
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
        $translator = new DelegationTranslator;
        $meta = new Aggregate(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            ['Label']
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
            )
            ->withChild(
                (new ValueObject(
                    new ClassName('foo'),
                    ['AnotherLabel'],
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
                        )
                ))
                    ->withProperty('content', new StringType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            (new Map('string', 'mixed'))
                                ->put('nullable', null),
                            new Types
                        )
                    )
            );
        $identity = $this->createMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $im = $translator->translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (entity:Label { id: {entity_identity} }) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) RETURN entity, entity_rel, entity_rel_child',
            $im->query()->cypher()
        );
        $this->assertCount(1, $im->query()->parameters());
        $this->assertSame(
            'foobar',
            $im->query()->parameters()->get('entity_identity')->value()
        );
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
        $translator = new DelegationTranslator;
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
        $identity = $this->createMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $im = $translator->translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (start)-[entity:type { id: {entity_identity} }]->(end) RETURN start, end, entity',
            $im->query()->cypher()
        );
        $this->assertCount(1, $im->query()->parameters());
        $this->assertSame(
            'foobar',
            $im->query()->parameters()->get('entity_identity')->value()
        );
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
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\Neo4j\ONM\Translation\IdentityMatchTranslator>
     */
    public function testThrowWhenGivingInvalidTranslatorsMap()
    {
        new DelegationTranslator(new Map('int', 'int'));
    }
}
