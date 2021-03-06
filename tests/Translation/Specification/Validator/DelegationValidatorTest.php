<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Specification;

use Innmind\Neo4j\ONM\{
    Translation\Specification\Validator\DelegationValidator,
    Translation\Specification\Validator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Type\DateType,
    Type\StringType,
    Type,
};
use Fixtures\Innmind\Neo4j\ONM\Specification\Property;
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class DelegationValidatorTest extends TestCase
{
    private $aggregate;
    private $relationship;

    public function setUp(): void
    {
        $this->aggregate = Aggregate::of(
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
        $this->relationship = Relationship::of(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Validator::class,
            new DelegationValidator
        );
    }

    public function testValidateAggregate()
    {
        $compCreated = new Property('created', Sign::equality(), null);
        $compRelCreated = new Property('rel.created', Sign::equality(), null);
        $compChildContent = new Property('rel.child.content', Sign::equality(), null);

        $this->assertTrue(
            (new DelegationValidator)(
                $compCreated,
                $this->aggregate
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $compRelCreated,
                $this->aggregate
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $compChildContent,
                $this->aggregate
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $compCreated
                    ->and($compRelCreated)
                    ->or($compChildContent->not()),
                $this->aggregate
            )
        );
    }

    public function testDoesntValidateAggregate()
    {
        $comp1 = new Property('foo', Sign::equality(), null);
        $comp2 = new Property('rel.foo', Sign::equality(), null);
        $comp3 = new Property('rel.child.foo', Sign::equality(), null);
        $comp4 = new Property('rel.child.foo.tooDeep', Sign::equality(), null);

        $this->assertFalse(
            (new DelegationValidator)(
                $comp1,
                $this->aggregate
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp2,
                $this->aggregate
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp3,
                $this->aggregate
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp1
                    ->and($comp2)
                    ->or($comp3->not())
                    ->or($comp4),
                $this->aggregate
            )
        );
    }

    public function testValidateRelationship()
    {
        $comp1 = new Property('created', Sign::equality(), null);
        $comp2 = new Property('start', Sign::equality(), null);
        $comp3 = new Property('end', Sign::equality(), null);

        $this->assertTrue(
            (new DelegationValidator)(
                $comp1,
                $this->relationship
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $comp2,
                $this->relationship
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $comp3,
                $this->relationship
            )
        );
        $this->assertTrue(
            (new DelegationValidator)(
                $comp1
                    ->and($comp2)
                    ->or($comp3->not()),
                $this->relationship
            )
        );
    }

    public function testDoesntValidateRelationship()
    {
        $comp1 = new Property('foo', Sign::equality(), null);
        $comp2 = new Property('foo.bar', Sign::equality(), null);
        $comp3 = new Property('start.id', Sign::equality(), null);

        $this->assertFalse(
            (new DelegationValidator)(
                $comp1,
                $this->relationship
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp2,
                $this->relationship
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp3,
                $this->relationship
            )
        );
        $this->assertFalse(
            (new DelegationValidator)(
                $comp1
                    ->and($comp2)
                    ->or($comp3->not()),
                $this->relationship
            )
        );
    }

    public function testThrowWhenInjectingInvalidValidator()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Neo4j\ONM\Translation\Specification\Validator>');

        new DelegationValidator(Map::of('int', 'int'));
    }
}
