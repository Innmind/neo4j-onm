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
    Type,
    Exception\SpecificationNotApplicable,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Neo4j\DBAL\Query;
use Innmind\Specification\Sign;
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
            SpecificationTranslator::class,
            new DelegationTranslator
        );
    }

    public function testTranslate()
    {
        $expected = new Property('created', Sign::equality(), null);
        $count = 0;
        $mock1 = $this->createMock(SpecificationTranslator::class);
        $mock1
            ->method('__invoke')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Aggregate::class, $meta);
                $this->assertSame($expected, $spec);

                return new IdentityMatch(
                    $this->createMock(Query::class),
                    Map::of('string', Entity::class)
                );
            }));
        $mock2 = $this->createMock(SpecificationTranslator::class);
        $mock2
            ->method('__invoke')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Relationship::class, $meta);
                $this->assertSame($expected, $spec);

                return new IdentityMatch(
                    $this->createMock(Query::class),
                    Map::of('string', Entity::class)
                );
            }));

        $translate = new DelegationTranslator(
            Map::of('string', SpecificationTranslator::class)
                (Aggregate::class, $mock1)
                (Relationship::class, $mock2)
        );

        $this->assertInstanceOf(
            IdentityMatch::class,
            $translate(
                Aggregate::of(
                    new ClassName('FQCN'),
                    new Identity('id', 'foo'),
                    Set::of('string', 'Label'),
                    Map::of('string', Type::class)
                        ('created', new DateType)
                ),
                $expected
            )
        );
        $this->assertInstanceOf(
            IdentityMatch::class,
            $translate(
                Relationship::of(
                    new ClassName('foo'),
                    new Identity('id', 'foo'),
                    new RelationshipType('type'),
                    new RelationshipEdge('start', 'foo', 'id'),
                    new RelationshipEdge('end', 'foo', 'id'),
                    Map::of('string', Type::class)
                        ('created', new DateType)
                ),
                $expected
            )
        );
        $this->assertSame(2, $count);
    }

    public function testThrowWhenInjectingInvalidTranslators()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Neo4j\ONM\Translation\SpecificationTranslator>');

        new DelegationTranslator(Map::of('int', 'int'));
    }

    public function testThrowWhenSpecificationNotApplicableToAggregate()
    {
        $this->expectException(SpecificationNotApplicable::class);

        (new DelegationTranslator)(
            Aggregate::of(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
                Set::of('string', 'Label')
            ),
            new Property('foo', Sign::equality(), null)
        );
    }

    public function testThrowWhenSpecificationNotApplicableToRelationship()
    {
        $this->expectException(SpecificationNotApplicable::class);

        (new DelegationTranslator)(
            Relationship::of(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id')
            ),
            new Property('foo', Sign::equality(), null)
        );
    }
}
