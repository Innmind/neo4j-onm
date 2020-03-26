<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Relationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Type,
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
    unwrap,
    first,
};
use PHPUnit\Framework\TestCase;

class ResultTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $translate = new ResultTranslator;
        $aggregate = Aggregate::of(
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
        $relationship = Relationship::of(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
        );

        $data = $translate(
            Result::fromRaw([
                'columns' => ['n', 'r'],
                'data' => [[
                    'row' => [
                        [
                            'id' => 42,
                            'created' => '2016-01-01T00:00:00+0200',
                        ],
                        [
                            'id' => 42,
                            'created' => '2016-01-03T00:00:00+0200',
                        ],
                    ],
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
                            [
                                'id' => 5,
                                'labels' => ['Node'],
                                'properties' => [
                                    'id' => 66,
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 6,
                                'labels' => ['Child'],
                                'properties' => [
                                    'id' => 24,
                                    'created' => '2016-01-02T00:00:00+0200',
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
                            [
                                'id' => 4,
                                'type' => 'CHILD1_OF',
                                'startNode' => 6,
                                'endNode' => 5,
                                'properties' => [
                                    'id' => 42,
                                    'created' => '2016-01-03T00:00:00+0200',
                                ],
                            ],
                        ],
                    ],
                ]],
            ]),
            (Map::of('string', Entity::class))
                ->put('n', $aggregate)
                ->put('r', $relationship)
        );

        $this->assertInstanceOf(Map::class, $data);
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame(Set::class, (string) $data->valueType());
        $this->assertSame(
            ['n', 'r'],
            unwrap($data->keys())
        );
        $this->assertCount(4, first($data->get('n')));
        $this->assertSame(
            ['id', 'created', 'rel', 'rel2'],
            unwrap(first($data->get('n'))->keys())
        );
        $this->assertSame(42, first($data->get('n'))->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', first($data->get('n'))->get('created'));
        $this->assertInstanceOf(Map::class, first($data->get('n'))->get('rel'));
        $this->assertSame(
            ['created', 'child'],
            unwrap(first($data->get('n'))->get('rel')->keys())
        );
        $this->assertSame(
            '2016-01-01T00:00:00+0200',
            first($data->get('n'))->get('rel')->get('created')
        );
        $this->assertInstanceOf(
            Map::class,
            first($data->get('n'))->get('rel')->get('child')
        );
        $this->assertSame(
            ['content'],
            unwrap(first($data->get('n'))->get('rel')->get('child')->keys())
        );
        $this->assertSame(
            'foo',
            first($data->get('n'))->get('rel')->get('child')->get('content')
        );
        $this->assertInstanceOf(Map::class, first($data->get('n'))->get('rel2'));
        $this->assertSame(2, first($data->get('n'))->get('rel2')->count());
        $this->assertSame(
            ['created', 'child'],
            unwrap(first($data->get('n'))->get('rel2')->keys())
        );
        $this->assertSame(
            '2016-01-03T00:00:00+0200',
            first($data->get('n'))->get('rel2')->get('created')
        );
        $this->assertInstanceOf(
            Map::class,
            first($data->get('n'))->get('rel2')->get('child')
        );
        $this->assertSame(
            ['content'],
            unwrap(first($data->get('n'))->get('rel2')->get('child')->keys())
        );
        $this->assertSame(
            'baz',
            first($data->get('n'))->get('rel2')->get('child')->get('content')
        );
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            unwrap(first($data->get('r'))->keys())
        );
        $this->assertSame(42, first($data->get('r'))->get('id'));
        $this->assertSame(24, first($data->get('r'))->get('start'));
        $this->assertSame(66, first($data->get('r'))->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', first($data->get('r'))->get('created'));
    }

    public function testTranslateWithoutExpectedVariable()
    {
        $translate = new ResultTranslator;
        $aggregate = Aggregate::of(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            Set::of('string', 'Label')
        );
        $relationship = Relationship::of(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id')
        );
        $data = $translate(
            Result::fromRaw([
                'columns' => [],
                'data' => [[
                    'row' => [],
                    'graph' => [
                        'nodes' => [],
                        'relationships' => [],
                    ],
                ]],
            ]),
            (Map::of('string', Entity::class))
                ->put('n', $aggregate)
                ->put('r', $relationship)
        );

        $this->assertCount(0, $data);
    }

    public function testThrowWhenEmptyInvalidMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\Neo4j\ONM\Translation\EntityTranslator>');
        new ResultTranslator(Map::of('string', 'callable'));
    }

    public function testThrowWhenEmptyInvalidVariableMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, Innmind\Neo4j\ONM\Metadata\Entity>');
        (new ResultTranslator)(
            $this->createMock(ResultInterface::class),
            Map::of('string', 'object')
        );
    }
}
