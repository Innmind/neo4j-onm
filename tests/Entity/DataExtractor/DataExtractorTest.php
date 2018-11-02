<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\DataExtractor,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Metadatas,
    Types,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class DataExtractorTest extends TestCase
{
    private $extractor;
    private $arClass;
    private $rClass;
    private $metadatas;

    public function setUp()
    {
        $ar = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $this->arClass = get_class($ar);
        $r = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $this->rClass  = get_class($r);

        $this->metadatas = new Metadatas(
            (new Aggregate(
                new ClassName($this->arClass),
                new Identity('uuid', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                ['Label']
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
                ->withChild(
                    (new ValueObject(
                        new ClassName('foo'),
                        ['AnotherLabel'],
                        (new ValueObjectRelationship(
                            new ClassName('foo'),
                            new RelationshipType('foo'),
                            'rel',
                            'child',
                            true
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
                ),
            (new Relationship(
                new ClassName($this->rClass),
                new Identity('uuid', 'foo'),
                new Repository('foo'),
                new Factory('foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'target'),
                new RelationshipEdge('end', Uuid::class, 'target')
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
        );
        $this->extractor = new DataExtractor($this->metadatas);
    }

    public function testExtractAggregateRoot()
    {
        $entity = new $this->arClass;
        $rel = new class {
            public $created;
            public $empty;
            public $child;
        };
        $child = new class {
            public $content;
            public $empty;
        };
        $entity->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->rel = $rel;
        $rel->created = new \DateTimeImmutable('2016-01-01');
        $rel->child = $child;
        $child->content = 'foo';

        $data = $this->extractor->extract($entity);

        $this->assertInstanceOf(MapInterface::class, $data);
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame('mixed', (string) $data->valueType());
        $this->assertSame(
            ['created', 'empty', 'uuid', 'rel'],
            $data->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('created')
        );
        $this->assertNull($data->get('empty'));
        $this->assertSame($u, $data->get('uuid'));
        $this->assertInstanceOf(MapInterface::class, $data->get('rel'));
        $this->assertSame('string', (string) $data->get('rel')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel')->valueType());
        $this->assertSame(
            ['created', 'empty', 'child'],
            $data->get('rel')->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('rel')->get('created')
        );
        $this->assertNull($data->get('rel')->get('empty'));
        $this->assertInstanceOf(
            MapInterface::class,
            $data->get('rel')->get('child')
        );
        $this->assertSame('string', (string) $data->get('rel')->get('child')->keyType());
        $this->assertSame('mixed', (string) $data->get('rel')->get('child')->valueType());
        $this->assertSame(
            ['content', 'empty'],
            $data->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame('foo', $data->get('rel')->get('child')->get('content'));
        $this->assertNull($data->get('rel')->get('child')->get('empty'));
    }

    public function testExtractRelationship()
    {
        $entity = new $this->rClass;
        $entity->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->start = new Uuid($s = '11111111-1111-1111-1111-111111111111');
        $entity->end = new Uuid($e = '11111111-1111-1111-1111-111111111111');

        $data = $this->extractor->extract($entity);

        $this->assertInstanceOf(MapInterface::class, $data);
        $this->assertSame('string', (string) $data->keyType());
        $this->assertSame('mixed', (string) $data->valueType());
        $this->assertSame(
            ['uuid', 'start', 'end', 'created', 'empty'],
            $data->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('created')
        );
        $this->assertNull($data->get('empty'));
        $this->assertSame($u, $data->get('uuid'));
        $this->assertSame($s, $data->get('start'));
        $this->assertSame($e, $data->get('end'));
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type MapInterface<string, Innmind\Neo4j\ONM\Entity\DataExtractor>
     */
    public function testThrowWhenInvalidExtractorMap()
    {
        new DataExtractor(
            $this->metadatas,
            new Map('string', 'callable')
        );
    }
}
