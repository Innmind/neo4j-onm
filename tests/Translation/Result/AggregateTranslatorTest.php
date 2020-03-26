<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\Result\AggregateTranslator,
    Translation\EntityTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Type,
    Exception\InvalidArgumentException,
    Exception\DomainException,
    Exception\MoreThanOneRelationshipFound,
};
use Innmind\Neo4j\DBAL\{
    Result\Result,
    Result as ResultInterface,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\{
    first,
    unwrap,
};
use PHPUnit\Framework\TestCase;

class AggregateTranslatorTest extends TestCase
{
    private $translate;
    private $meta;

    public function setUp(): void
    {
        $this->translate = new AggregateTranslator;
        $this->meta = Aggregate::of(
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
                ),
                Child::of(
                    new ClassName('foo'),
                    Set::of('string', 'AnotherLabel'),
                    Child\Relationship::of(
                        new ClassName('foo'),
                        new RelationshipType('CHILD2_OF'),
                        'rel2',
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
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityTranslator::class,
            $this->translate
        );
    }

    public function testTranslate()
    {
        $data = ($this->translate)(
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

        $this->assertInstanceOf(Set::class, $data);
        $this->assertSame(Map::class, (string) $data->type());
        $this->assertCount(1, $data);
        $data = first($data);
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame('mixed', (string) $data->valueType());
        $this->assertCount(4, $data);
        $this->assertSame(
            ['id', 'created', 'rel', 'rel2'],
            unwrap($data->keys())
        );
        $this->assertSame(42, $data->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', $data->get('created'));
        $this->assertInstanceOf(Map::class, $data->get('rel'));
        $this->assertSame('string', (string) $data->get('rel')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel')->valueType());
        $this->assertSame(
            ['created', 'child'],
            unwrap($data->get('rel')->keys())
        );
        $this->assertSame(
            '2016-01-01T00:00:00+0200',
            $data->get('rel')->get('created')
        );
        $this->assertInstanceOf(
            Map::class,
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
            unwrap($data->get('rel')->get('child')->keys())
        );
        $this->assertSame(
            'foo',
            $data->get('rel')->get('child')->get('content')
        );
        $this->assertInstanceOf(Map::class, $data->get('rel2'));
        $this->assertSame('string', (string) $data->get('rel2')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel2')->valueType());
        $this->assertCount(2, $data->get('rel2'));
        $this->assertSame(
            ['created', 'child'],
            unwrap($data->get('rel2')->keys())
        );
        $this->assertSame(
            '2016-01-03T00:00:00+0200',
            $data->get('rel2')->get('created')
        );
        $this->assertInstanceOf(
            Map::class,
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
            unwrap($data->get('rel2')->get('child')->keys())
        );
        $this->assertSame(
            'baz',
            $data->get('rel2')->get('child')->get('content')
        );
    }

    public function testTranslateMultipleNodes()
    {
        $meta = Aggregate::of(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            Set::of('string', 'Label'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
        );

        $data = ($this->translate)(
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

        $this->assertInstanceOf(Set::class, $data);
        $this->assertCount(2, $data);
        $data = unwrap($data);
        $this->assertSame(
            ['id', 'created'],
            unwrap(\current($data)->keys())
        );
        $this->assertSame(42, \current($data)->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', \current($data)->get('created'));
        \next($data);
        $this->assertSame(
            ['id', 'created'],
            unwrap(\current($data)->keys())
        );
        $this->assertSame(43, \current($data)->get('id'));
        $this->assertSame('2016-01-02T00:00:00+0200', \current($data)->get('created'));
    }

    public function testThrowWhenMoreThanOneRelationshipFound()
    {
        $this->expectException(MoreThanOneRelationshipFound::class);
        $this->expectExceptionMessage('More than one relationship found on "FQCN::rel2"');

        ($this->translate)(
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

    public function testThrowWhenTranslatingNonSupportedEntity()
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->translate)(
            'r',
            $this->createMock(Entity::class),
            $this->createMock(ResultInterface::class)
        );
    }

    public function testThrowWhenTranslatingEmptyVariable()
    {
        $this->expectException(DomainException::class);

        ($this->translate)(
            '',
            $this->meta,
            $this->createMock(ResultInterface::class)
        );
    }
}
