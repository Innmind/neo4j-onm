<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\DelegationTranslator,
    Translation\SpecificationTranslator,
    Metadata\Aggregate,
    Metadata\Relationship,
    IdentityMatch,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DelegationTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationTranslator::class,
            new DelegationTranslator
        );
    }

    public function testTranslate()
    {
        $expected = new Property('created', '=', null);
        $count = 0;
        $m1 = $this->createMock(SpecificationTranslator::class);
        $m1
            ->method('__invoke')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Aggregate::class, $meta);
                $this->assertSame($expected, $spec);

                return new IdentityMatch(
                    $this->createMock(Query::class),
                    new Map('string', Entity::class)
                );
            }));
        $m2 = $this->createMock(SpecificationTranslator::class);
        $m2
            ->method('__invoke')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Relationship::class, $meta);
                $this->assertSame($expected, $spec);

                return new IdentityMatch(
                    $this->createMock(Query::class),
                    new Map('string', Entity::class)
                );
            }));

        $translate = new DelegationTranslator(
            (new Map('string', SpecificationTranslator::class))
                ->put(Aggregate::class, $m1)
                ->put(Relationship::class, $m2)
        );

        $this->assertInstanceOf(
            IdentityMatch::class,
            $translate(
                (new Aggregate(
                    new ClassName('FQCN'),
                    new Identity('id', 'foo'),
                    ['Label']
                ))
                    ->withProperty('created', new DateType),
                $expected
            )
        );
        $this->assertInstanceOf(
            IdentityMatch::class,
            $translate(
                (new Relationship(
                    new ClassName('foo'),
                    new Identity('id', 'foo'),
                    new RelationshipType('type'),
                    new RelationshipEdge('start', 'foo', 'id'),
                    new RelationshipEdge('end', 'foo', 'id')
                ))
                    ->withProperty('created', new DateType),
                $expected
            )
        );
        $this->assertSame(2, $count);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\Neo4j\ONM\Translation\SpecificationTranslator>
     */
    public function testThrowWhenInjectingInvalidTranslators()
    {
        new DelegationTranslator(new Map('int', 'int'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicable
     */
    public function testThrowWhenSpecificationNotApplicableToAggregate()
    {
        (new DelegationTranslator)(
            new Aggregate(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
                ['Label']
            ),
            new Property('foo', '=', null)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicable
     */
    public function testThrowWhenSpecificationNotApplicableToRelationship()
    {
        (new DelegationTranslator)(
            new Relationship(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id')
            ),
            new Property('foo', '=', null)
        );
    }
}
