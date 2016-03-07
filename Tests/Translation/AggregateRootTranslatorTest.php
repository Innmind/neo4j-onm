<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation;

use Innmind\Neo4j\ONM\{
    Translation\AggregateRootTranslator,
    Metadata\AggregateRoot,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType
};
use Innmind\Neo4j\DBAL\Result;
use Innmind\Immutable\{
    Collection,
    CollectionInterface
};

class AggregateRootTranslatorTest extends \PHPUnit_Framework_TestCase
{
    private $t;
    private $m;

    public function setUp()
    {
        $this->t = new AggregateRootTranslator;
        $this->m = new AggregateRoot(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            ['Label']
        );
        $this->m = $this->m
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
                        'child',
                        true
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
            )
            ->withChild(
                (new ValueObject(
                    new ClassName('foo'),
                    ['AnotherLabel'],
                    (new ValueObjectRelationship(
                        new ClassName('foo'),
                        new RelationshipType('CHILD2_OF'),
                        'rel2',
                        'child',
                        false
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
    }

    public function testTranslate()
    {
        $data = $this->t->translate(
            'n',
            $this->m,
            Result::fromRaw([
                'columns' => ['n'],
                'data' => [[
                    'row' => [[
                        'id' => 42,
                        'created' => '2016-01-01T00:00:00+0200',
                    ]],
                    'graph' => [
                        'nodes' => [
                            [
                                'id' => 1,
                                'labels' => ['Node'],
                                'properties' => [
                                    'id' => 42,
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 2,
                                'labels' => ['Child'],
                                'properties' => [
                                    'content' => 'foo',
                                ],
                            ],
                            [
                                'id' => 3,
                                'labels' => ['Child'],
                                'properties' => [
                                    'content' => 'bar',
                                ],
                            ],
                            [
                                'id' => 4,
                                'labels' => ['Child2'],
                                'properties' => [
                                    'content' => 'baz',
                                ],
                            ],
                        ],
                        'relationships' => [
                            [
                                'id' => 1,
                                'type' => 'CHILD1_OF',
                                'startNode' => 2,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 2,
                                'type' => 'CHILD1_OF',
                                'startNode' => 3,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-02T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 3,
                                'type' => 'CHILD2_OF',
                                'startNode' => 4,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-03T00:00:00+0200',
                                ],
                            ],
                        ],
                    ],
                ]],
            ])
        );

        $this->assertInstanceOf(CollectionInterface::class, $data);
        $this->assertSame(4, $data->count());
        $this->assertSame(
            ['id', 'created', 'rel', 'rel2'],
            $data->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', $data->get('created'));
        $this->assertInstanceOf(CollectionInterface::class, $data->get('rel'));
        $this->assertSame(2, $data->get('rel')->count());
        $this->assertSame(
            ['created', 'child'],
            $data->get('rel')->get(0)->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-01T00:00:00+0200',
            $data->get('rel')->get(0)->get('created')
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('rel')->get(0)->get('child')
        );
        $this->assertSame(
            ['content'],
            $data->get('rel')->get(0)->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'foo',
            $data->get('rel')->get(0)->get('child')->get('content')
        );
        $this->assertSame(
            ['created', 'child'],
            $data->get('rel')->get(1)->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-02T00:00:00+0200',
            $data->get('rel')->get(1)->get('created')
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('rel')->get(1)->get('child')
        );
        $this->assertSame(
            ['content'],
            $data->get('rel')->get(1)->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'bar',
            $data->get('rel')->get(1)->get('child')->get('content')
        );
        $this->assertInstanceOf(CollectionInterface::class, $data->get('rel2'));
        $this->assertSame(2, $data->get('rel2')->count());
        $this->assertSame(
            ['created', 'child'],
            $data->get('rel2')->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-03T00:00:00+0200',
            $data->get('rel2')->get('created')
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('rel2')->get('child')
        );
        $this->assertSame(
            ['content'],
            $data->get('rel2')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'baz',
            $data->get('rel2')->get('child')->get('content')
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\MoreThanOneRelationshipFoundException
     * @expectedExceptionMessage More than one relationship found on "FQCN::rel2"
     */
    public function testThrowWhenMoreThanOneRelationshipFound()
    {
        $this->t->translate(
            'n',
            $this->m,
            Result::fromRaw([
                'columns' => ['n'],
                'data' => [[
                    'row' => [[
                        'id' => 42,
                        'created' => '2016-01-01T00:00:00+0200',
                    ]],
                    'graph' => [
                        'nodes' => [
                            [
                                'id' => 1,
                                'labels' => ['Node'],
                                'properties' => [
                                    'id' => 42,
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 2,
                                'labels' => ['Child'],
                                'properties' => [
                                    'content' => 'foo',
                                ],
                            ],
                            [
                                'id' => 3,
                                'labels' => ['Child'],
                                'properties' => [
                                    'content' => 'bar',
                                ],
                            ],
                            [
                                'id' => 4,
                                'labels' => ['Child2'],
                                'properties' => [
                                    'content' => 'baz',
                                ],
                            ],
                        ],
                        'relationships' => [
                            [
                                'id' => 1,
                                'type' => 'CHILD1_OF',
                                'startNode' => 2,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 2,
                                'type' => 'CHILD2_OF',
                                'startNode' => 3,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-02T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 3,
                                'type' => 'CHILD2_OF',
                                'startNode' => 4,
                                'endNode' => 1,
                                'properties' => [
                                    'created' => '2016-01-03T00:00:00+0200',
                                ],
                            ],
                        ],
                    ],
                ]],
            ])
        );
    }
}
