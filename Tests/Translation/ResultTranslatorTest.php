<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Translation;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Metadata\AggregateRoot,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\Relationship,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType
};
use Innmind\Neo4j\DBAL\Result;
use Innmind\Immutable\{
    Collection,
    Map,
    MapInterface,
    CollectionInterface
};

class ResultTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testTranslate()
    {
        $t = new ResultTranslator;
        $aggregate = new AggregateRoot(
            new ClassName('FQCN'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            ['Label']
        );
        $aggregate = $aggregate
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
        $relationship = new Relationship(
            new ClassName('foo'),
            new Identity('id', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', 'foo', 'id'),
            new RelationshipEdge('end', 'foo', 'id')
        );
        $relationship = $relationship
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    new Collection(['nullable' => null])
                )
            );

        $data = $t->translate(
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
            (new Map('string', EntityInterface::class))
                ->put('n', $aggregate)
                ->put('r', $relationship)
        );

        $this->assertInstanceOf(MapInterface::class, $data);
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame(CollectionInterface::class, (string) $data->valueType());
        $this->assertSame(
            ['n', 'r'],
            $data->keys()->toPrimitive()
        );
        $this->assertSame(4, $data->get('n')->count());
        $this->assertSame(
            ['id', 'created', 'rel', 'rel2'],
            $data->get('n')->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get('n')->get('id'));
        $this->assertSame('2016-01-01T00:00:00+0200', $data->get('n')->get('created'));
        $this->assertInstanceOf(CollectionInterface::class, $data->get('n')->get('rel'));
        $this->assertSame(
            ['created', 'child'],
            $data->get('n')->get('rel')->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-01T00:00:00+0200',
            $data->get('n')->get('rel')->get('created')
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('n')->get('rel')->get('child')
        );
        $this->assertSame(
            ['content'],
            $data->get('n')->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'foo',
            $data->get('n')->get('rel')->get('child')->get('content')
        );
        $this->assertInstanceOf(CollectionInterface::class, $data->get('n')->get('rel2'));
        $this->assertSame(2, $data->get('n')->get('rel2')->count());
        $this->assertSame(
            ['created', 'child'],
            $data->get('n')->get('rel2')->keys()->toPrimitive()
        );
        $this->assertSame(
            '2016-01-03T00:00:00+0200',
            $data->get('n')->get('rel2')->get('created')
        );
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('n')->get('rel2')->get('child')
        );
        $this->assertSame(
            ['content'],
            $data->get('n')->get('rel2')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame(
            'baz',
            $data->get('n')->get('rel2')->get('child')->get('content')
        );
        $this->assertSame(
            ['id', 'start', 'end', 'created'],
            $data->get('r')->keys()->toPrimitive()
        );
        $this->assertSame(42, $data->get('r')->get('id'));
        $this->assertSame(24, $data->get('r')->get('start'));
        $this->assertSame(66, $data->get('r')->get('end'));
        $this->assertSame('2016-01-03T00:00:00+0200', $data->get('r')->get('created'));
    }
}
