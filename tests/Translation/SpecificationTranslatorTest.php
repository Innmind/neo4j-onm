<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\SpecificationTranslator,
    Translation\SpecificationTranslatorInterface,
    Translation\Specification\ValidatorInterface,
    Translation\Specification\Validator,
    Translation\Specification\Validator\AggregateValidator,
    Translation\Specification\Validator\RelationshipValidator,
    Metadata\Aggregate,
    Metadata\Relationship,
    IdentityMatch,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Type\DateType
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Immutable\Map;

class SpecificationTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testTranslate()
    {
        $expected = new Property('created', '=', null);
        $count = 0;
        $m1 = $this->createMock(SpecificationTranslatorInterface::class);
        $m1
            ->method('translate')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Aggregate::class, $meta);
                $this->assertSame($expected, $spec);

                return $this
                    ->getMockBuilder(IdentityMatch::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            }));
        $m2 = $this->createMock(SpecificationTranslatorInterface::class);
        $m2
            ->method('translate')
            ->will($this->returnCallback(function($meta, $spec) use ($expected, &$count) {
                ++$count;
                $this->assertInstanceOf(Relationship::class, $meta);
                $this->assertSame($expected, $spec);

                return $this
                    ->getMockBuilder(IdentityMatch::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            }));

        $t = new SpecificationTranslator(
            (new Map('string', SpecificationTranslatorInterface::class))
                ->put(Aggregate::class, $m1)
                ->put(Relationship::class, $m2)
        );

        $this->assertInstanceOf(
            IdentityMatch::class,
            $t->translate(
                (new Aggregate(
                    new ClassName('FQCN'),
                    new Identity('id', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    ['Label']
                ))
                    ->withProperty('created', new DateType),
                $expected
            )
        );
        $this->assertInstanceOf(
            IdentityMatch::class,
            $t->translate(
                (new Relationship(
                    new ClassName('foo'),
                    new Identity('id', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
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
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInjectingInvalidTranslators()
    {
        new SpecificationTranslator(new Map('int', 'int'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableException
     */
    public function testThrowWhenSpecificationNotApplicableToAggregate()
    {
        (new SpecificationTranslator)->translate(
            new Aggregate(
                new ClassName('FQCN'),
                new Identity('id', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new Alias('foo'),
                ['Label']
            ),
            new Property('foo', '=', null)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\SpecificationNotApplicableException
     */
    public function testThrowWhenSpecificationNotApplicableToRelationship()
    {
        (new SpecificationTranslator)->translate(
            new Relationship(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new Alias('foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id')
            ),
            new Property('foo', '=', null)
        );
    }
}
