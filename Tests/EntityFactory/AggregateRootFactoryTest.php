<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory\AggregateRootFactory,
    Metadata\AggregateRoot,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    IdentityInterface
};
use Innmind\Immutable\{
    Collection,
    SetInterface
};

class AggregateRootFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testMake()
    {
        $f = new AggregateRootFactory;

        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $rel = new class {
            public $created;
            public $empty;
            public $child;
        };
        $child = new class {
            public $content;
            public $empty;
        };
        $meta = new AggregateRoot(
            new ClassName(get_class($entity)),
            new Identity('uuid', 'foo'),
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
                    new ClassName(get_class($child)),
                    ['AnotherLabel'],
                    (new ValueObjectRelationship(
                        new ClassName(get_class($rel)),
                        new RelationshipType('foo'),
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

        $ar = $f->make(
            $identity = new Uuid('11111111-1111-1111-1111-111111111111'),
            $meta,
            new Collection([
                'uuid' => 24,
                'created' => '2016-01-01T00:00:00+0200',
                'rel' => new Collection([
                    'created' => '2016-01-01T00:00:00+0200',
                    'child' => new Collection([
                        'content' => 'foo',
                    ]),
                ]),
            ])
        );

        $this->assertInstanceOf(get_class($entity), $ar);
        $this->assertSame($identity, $ar->uuid);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->created->format('c')
        );
        $this->assertSame(null, $ar->empty);
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $ar->rel->created
        );
        $this->assertSame(
            '2016-01-01T00:00:00+02:00',
            $ar->rel->created->format('c')
        );
        $this->assertSame(null, $ar->rel->empty);
        $this->assertInstanceOf(
            get_class($child),
            $ar->rel->child
        );
        $this->assertSame('foo', $ar->rel->child->content);
        $this->assertSame(null, $ar->rel->child->empty);
    }

    /**
     * @expectedException Innmind\neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToBuildNonAggregateRoot()
    {
        (new AggregateRootFactory)->make(
            $this->getMock(IdentityInterface::class),
            $this->getMock(EntityInterface::class),
            new Collection([])
        );
    }
}
