<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation;

use Innmind\Neo4j\ONM\{
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
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    IdentityInterface,
    IdentityMatch
};
use Innmind\Immutable\{
    Collection,
    MapInterface,
    Map
};

class IdentityMatchTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testTranslateAggregate()
    {
        $t = new IdentityMatchTranslator;
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
                    new Collection(['nullable' => null])
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
                                new Collection(['nullable' => null])
                            )
                        )
                ))
                    ->withProperty('content', new StringType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            new Collection(['nullable' => null])
                        )
                    )
            );
        $identity = $this->getMock(IdentityInterface::class);
        $identity
            ->method('value')
            ->willReturn('foobar');
        $im = $t->translate($meta, $identity);

        $this->assertInstanceOf(IdentityMatch::class, $im);
        $this->assertSame(
            'MATCH (entity:Label { id: {entity_identity} }) WITH entity MATCH (entity)<-[entity_rel:CHILD1_OF]-(entity_rel_child:AnotherLabel) RETURN entity',
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

    public function testTranslateRelationship()
    {
        $t = new IdentityMatchTranslator;
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
        $identity = $this->getMock(IdentityInterface::class);
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

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenGivingInvalidTranslatorsMap()
    {
        new IdentityMatchTranslator(new Map('int', 'int'));
    }
}
