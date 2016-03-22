<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation;

use Innmind\Neo4j\ONM\{
    Translation\Result\RelationshipTranslator,
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
    Type\StringType
};
use Innmind\Neo4j\DBAL\{
    Result,
    ResultInterface
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class RelationshipTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testTranslate()
    {
        $t = new RelationshipTranslator;
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
                    new Collection(['nullable' => null])
                )
            );

        $data = $t->translate(
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

        $this->assertInstanceOf(CollectionInterface::class, $data);
        $data = $data->get(0);
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
        $t = new RelationshipTranslator;
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
                    new Collection(['nullable' => null])
                )
            );

        $data = $t->translate(
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

        $this->assertInstanceOf(CollectionInterface::class, $data);
        $this->assertSame(2, $data->count());
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->get(0)->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get(0)->get('id'));
        $this->assertSame(24, $data->get(0)->get('start'));
        $this->assertSame(66, $data->get(0)->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', $data->get(0)->get('created'));
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->get(1)->keys()->toPrimitive()
        );
        $this->assertSame(43, $data->get(1)->get('id'));
        $this->assertSame(24, $data->get(1)->get('start'));
        $this->assertSame(66, $data->get(1)->get('end'));
        $this->assertSame('2016-01-04T00:00:00+0200', $data->get(1)->get('created'));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenTranslatingNonSupportedEntity()
    {
        (new RelationshipTranslator)->translate(
            'r',
            $this->getMock(EntityInterface::class),
            $this->getMock(ResultInterface::class)
        );
    }
}
