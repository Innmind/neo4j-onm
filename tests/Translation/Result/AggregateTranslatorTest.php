<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\Result\AggregateTranslator,
    Translation\EntityTranslator,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Types
};
use Innmind\Neo4j\DBAL\{
    Result\Result,
    Result as ResultInterface
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    private $translator;
    private $meta;

    public function setUp()
    {
        $this->translator = new AggregateTranslator;
        $this->meta = new Aggregate(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            ['Label']
        );
        $this->meta = $this->meta
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
            )
            ->withChild(
                (new ValueObject(
                    new ClassName('foo'),
                    ['AnotherLabel'],
                    (new ValueObjectRelationship(
                        new ClassName('foo'),
                        new RelationshipType('CHILD2_OF'),
                        'rel2',
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
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityTranslator::class,
            $this->translator
        );
    }

    public function testTranslate()
    {
        $data = $this->translator->translate(
            'n',
            $this->meta,
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

        $this->assertInstanceOf(SetInterface::class, $data);
        $this->assertSame(MapInterface::class, (string) $data->type());
        $this->assertCount(1, $data);
        $data = $data->current();
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame('mixed', (string) $data->valueType());
        $this->assertCount(4, $data);
        $this->assertSame(
            ['id', 'created', 'rel', 'rel2'],
            $data->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', $data->get('created'));
        $this->assertInstanceOf(MapInterface::class, $data->get('rel'));
        $this->assertSame('string', (string) $data->get('rel')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel')->valueType());
        $this->assertSame(
            ['created', 'child'],
            $data->get('rel')->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-01T00:00:00+0200',
            $data->get('rel')->get('created')
        );
        $this->assertInstanceOf(
            MapInterface::class,
            $data->get('rel')->get('child')
        );
        $this->assertSame(
            'string',
            (string) $data->get('rel')->get('child')->keyType()
        );
        $this->assertSame(
            'mixed',
            (string) $data->get('rel')->get('child')->valueType()
        );
        $this->assertSame(
            ['content'],
            $data->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'foo',
            $data->get('rel')->get('child')->get('content')
        );
        $this->assertInstanceOf(MapInterface::class, $data->get('rel2'));
        $this->assertSame('string', (string) $data->get('rel2')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel2')->valueType());
        $this->assertCount(2, $data->get('rel2'));
        $this->assertSame(
            ['created', 'child'],
            $data->get('rel2')->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-03T00:00:00+0200',
            $data->get('rel2')->get('created')
        );
        $this->assertInstanceOf(
            MapInterface::class,
            $data->get('rel2')->get('child')
        );
        $this->assertSame(
            'string',
            (string) $data->get('rel2')->get('child')->keyType()
        );
        $this->assertSame(
            'mixed',
            (string) $data->get('rel2')->get('child')->valueType()
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

    public function testTranslateMultipleNodes()
    {
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
            );

        $data = $this->translator->translate(
            'n',
            $meta,
            Result::fromRaw([
                'columns' => ['n'],
                'data' => [
                    [
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
                                    'labels' => ['Node'],
                                    'properties' => [
                                        'id' => 43,
                                        'created' => '2016-01-02T00:00:00+0200',
                                    ],
                                ],
                            ],
                            'relationships' => [],
                        ],
                    ],
                    [
                        'row' => [[
                            'id' => 43,
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
                                    'labels' => ['Node'],
                                    'properties' => [
                                        'id' => 43,
                                        'created' => '2016-01-02T00:00:00+0200',
                                    ],
                                ],
                            ],
                            'relationships' => [],
                        ],
                    ]
                ],
            ])
        );

        $this->assertInstanceOf(SetInterface::class, $data);
        $this->assertCount(2, $data);
        $this->assertSame(
            ['id', 'created'],
            $data->current()->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->current()->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', $data->current()->get('created'));
        $data->next();
        $this->assertSame(
            ['id', 'created'],
            $data->current()->keys()->toPrimitive()
        );
        $this->assertSame(43, $data->current()->get('id'));
        $this->assertSame('2016-01-02T00:00:00+0200', $data->current()->get('created'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\MoreThanOneRelationshipFound
     * @expectedExceptionMessage More than one relationship found on "FQCN::rel2"
     */
    public function testThrowWhenMoreThanOneRelationshipFound()
    {
        $this->translator->translate(
            'n',
            $this->meta,
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

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTranslatingNonSupportedEntity()
    {
        $this->translator->translate(
            'r',
            $this->createMock(Entity::class),
            $this->createMock(ResultInterface::class)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTranslatingEmptyVariable()
    {
        $this->translator->translate(
            '',
            $this->meta,
            $this->createMock(ResultInterface::class)
        );
    }
}
