<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\DataExtractor,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Metadatas,
    Type,
    Exception\TypeError,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class DataExtractorTest extends TestCase
{
    private $extract;
    private $aggregateRootClass;
    private $relationshipClass;
    private $metadatas;

    public function setUp(): void
    {
        $aggregateRoot = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
        $this->aggregateRootClass = get_class($aggregateRoot);
        $relationship = new class {
            public $uuid;
            public $created;
            public $empty;
            public $start;
            public $end;
        };
        $this->relationshipClass  = get_class($relationship);

        $this->metadatas = new Metadatas(
            Aggregate::of(
                new ClassName($this->aggregateRootClass),
                new Identity('uuid', 'foo'),
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
                            new RelationshipType('foo'),
                            'rel',
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
            ),
            Relationship::of(
                new ClassName($this->relationshipClass),
                new Identity('uuid', 'foo'),
                new RelationshipType('type'),
                new RelationshipEdge('start', Uuid::class, 'target'),
                new RelationshipEdge('end', Uuid::class, 'target'),
                Map::of('string', Type::class)
                    ('created', new DateType)
                    ('empty', StringType::nullable())
            )
        );
        $this->extract = new DataExtractor($this->metadatas);
    }

    public function testExtractAggregateRoot()
    {
        $entity = new $this->aggregateRootClass;
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

        $data = ($this->extract)($entity);

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
        $entity = new $this->relationshipClass;
        $entity->uuid = new Uuid($uuid = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->start = new Uuid($start = '11111111-1111-1111-1111-111111111111');
        $entity->end = new Uuid($end = '11111111-1111-1111-1111-111111111111');

        $data = ($this->extract)($entity);

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
        $this->assertSame($uuid, $data->get('uuid'));
        $this->assertSame($start, $data->get('start'));
        $this->assertSame($end, $data->get('end'));
    }

    public function testThrowWhenInvalidExtractorMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, Innmind\Neo4j\ONM\Entity\DataExtractor>');

        new DataExtractor(
            $this->metadatas,
            new Map('string', 'callable')
        );
    }
}
