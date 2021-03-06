<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\Result\RelationshipTranslator,
    Translation\EntityTranslator,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Type,
    Exception\InvalidArgumentException,
    Exception\DomainException,
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

class RelationshipTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityTranslator::class,
            new RelationshipTranslator
        );
    }

    public function testTranslate()
    {
        $translate = new RelationshipTranslator;
        $meta = Relationship::of(
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
            'r',
            $meta,
            Result::fromRaw([
                'columns' => ['r'],
                'data' => [[
                    'row' => [[
                        'id' => 42,
                        'created' => '2016-01-03T00:00:00+0200',
                    ]],
                    'graph' => [
                        'nodes' => [
                            [
                                'id' => 1,
                                'labels' => ['Node'],
                                'properties' => [
                                    'id' => 66,
                                    'created' => '2016-01-01T00:00:00+0200',
                                ],
                            ],
                            [
                                'id' => 2,
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
                                    'id' => 42,
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
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            unwrap($data->keys()),
        );
        $this->assertSame(42, $data->get('id'));
        $this->assertSame(24, $data->get('start'));
        $this->assertSame(66, $data->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', $data->get('created'));
    }

    public function testTranslateMultipleRelationships()
    {
        $translate = new RelationshipTranslator;
        $meta = Relationship::of(
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
            'r',
            $meta,
            Result::fromRaw([
                'columns' => ['r'],
                'data' => [
                    [
                        'row' => [[
                            'id' => 42,
                            'created' => '2016-01-03T00:00:00+0200',
                        ]],
                        'graph' => [
                            'nodes' => [
                                [
                                    'id' => 1,
                                    'labels' => ['Node'],
                                    'properties' => [
                                        'id' => 66,
                                        'created' => '2016-01-01T00:00:00+0200',
                                    ],
                                ],
                                [
                                    'id' => 2,
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
                                        'id' => 42,
                                        'created' => '2016-01-03T00:00:00+0200',
                                    ],
                                ],
                                [
                                    'id' => 2,
                                    'type' => 'CHILD1_OF',
                                    'startNode' => 2,
                                    'endNode' => 1,
                                    'properties' => [
                                        'id' => 43,
                                        'created' => '2016-01-04T00:00:00+0200',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'row' => [[
                            'id' => 43,
                            'created' => '2016-01-04T00:00:00+0200',
                        ]],
                        'graph' => [
                            'nodes' => [
                                [
                                    'id' => 1,
                                    'labels' => ['Node'],
                                    'properties' => [
                                        'id' => 66,
                                        'created' => '2016-01-01T00:00:00+0200',
                                    ],
                                ],
                                [
                                    'id' => 2,
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
                                        'id' => 42,
                                        'created' => '2016-01-03T00:00:00+0200',
                                    ],
                                ],
                                [
                                    'id' => 2,
                                    'type' => 'CHILD1_OF',
                                    'startNode' => 2,
                                    'endNode' => 1,
                                    'properties' => [
                                        'id' => 43,
                                        'created' => '2016-01-04T00:00:00+0200',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertInstanceOf(Set::class, $data);
        $this->assertCount(2, $data);
        $data = unwrap($data);
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            unwrap(\current($data)->keys())
        );
        $this->assertSame(42, \current($data)->get('id'));
        $this->assertSame(24, \current($data)->get('start'));
        $this->assertSame(66, \current($data)->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', \current($data)->get('created'));
        \next($data);
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            unwrap(\current($data)->keys()),
        );
        $this->assertSame(43, \current($data)->get('id'));
        $this->assertSame(24, \current($data)->get('start'));
        $this->assertSame(66, \current($data)->get('end'));
        $this->assertSame('2016-01-04T00:00:00+0200', \current($data)->get('created'));
    }

    public function testThrowWhenTranslatingNonSupportedEntity()
    {
        $this->expectException(InvalidArgumentException::class);

        (new RelationshipTranslator)(
            'r',
            $this->createMock(Entity::class),
            $this->createMock(ResultInterface::class)
        );
    }

    public function testThrowWhenTranslatingWhenEmptyVariable()
    {
        $this->expectException(DomainException::class);

        (new RelationshipTranslator)(
            '',
            Relationship::of(
                new ClassName('foo'),
                new Identity('id', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', 'foo', 'id'),
                new RelationshipEdge('end', 'foo', 'id')
            ),
            $this->createMock(ResultInterface::class)
        );
    }
}
