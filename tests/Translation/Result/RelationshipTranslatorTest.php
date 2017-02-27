<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Translation\Result;

use Innmind\Neo4j\ONM\{
    Translation\Result\RelationshipTranslator,
    Translation\EntityTranslatorInterface,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Types
};
use Innmind\Neo4j\DBAL\{
    Result,
    ResultInterface
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface
};
use PHPUnit\Framework\TestCase;

class RelationshipTranslatorTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EntityTranslatorInterface::class,
            new RelationshipTranslator
        );
    }

    public function testTranslate()
    {
        $translator = new RelationshipTranslator;
        $meta = new Relationship(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id')
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

        $data = $translator->translate(
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

        $this->assertInstanceOf(SetInterface::class, $data);
        $this->assertSame(MapInterface::class, (string) $data->type());
        $this->assertCount(1, $data);
        $data = $data->current();
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame('mixed', (string) $data->valueType());
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get('id'));
        $this->assertSame(24, $data->get('start'));
        $this->assertSame(66, $data->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', $data->get('created'));
    }

    public function testTranslateMultipleRelationships()
    {
        $translator = new RelationshipTranslator;
        $meta = new Relationship(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id')
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

        $data = $translator->translate(
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
                    ]
                ],
            ])
        );

        $this->assertInstanceOf(SetInterface::class, $data);
        $this->assertCount(2, $data);
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->current()->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->current()->get('id'));
        $this->assertSame(24, $data->current()->get('start'));
        $this->assertSame(66, $data->current()->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', $data->current()->get('created'));
        $data->next();
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->current()->keys()->toPrimitive()
        );
        $this->assertSame(43, $data->current()->get('id'));
        $this->assertSame(24, $data->current()->get('start'));
        $this->assertSame(66, $data->current()->get('end'));
        $this->assertSame('2016-01-04T00:00:00+0200', $data->current()->get('created'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTranslatingNonSupportedEntity()
    {
        (new RelationshipTranslator)->translate(
            'r',
            $this->createMock(EntityInterface::class),
            $this->createMock(ResultInterface::class)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTranslatingWhenEmptyVariable()
    {
        (new RelationshipTranslator)->translate(
            '',
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
            $this->createMock(ResultInterface::class)
        );
    }
}
