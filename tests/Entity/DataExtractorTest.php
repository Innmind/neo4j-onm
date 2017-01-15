<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor,
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Metadatas
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class DataExtractorTest extends \PHPUnit_Framework_TestCase
{
    private $e;
    private $arClass;
    private $rClass;

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

        $m = new Metadatas;
        $m
            ->register(
                (new Aggregate(
                    new ClassName($this->arClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    ['Label']
                ))
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
                                new RelationshipType('foo'),
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
            )
            ->register(
                (new Relationship(
                    new ClassName($this->rClass),
                    new Identity('uuid', 'foo'),
                    new Repository('foo'),
                    new Factory('foo'),
                    new Alias('foo'),
                    new RelationshipType('type'),
                    new RelationshipEdge('start', Uuid::class, 'target'),
                    new RelationshipEdge('end', Uuid::class, 'target')
                ))
                    ->withProperty('created', new DateType)
                    ->withProperty(
                        'empty',
                        StringType::fromConfig(
                            new Collection(['nullable' => null])
                        )
                    )
            );
        $this->e = new DataExtractor($m);
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

        $data = $this->e->extract($entity);

        $this->assertInstanceOf(CollectionInterface::class, $data);
        $this->assertSame(
            ['created', 'empty', 'uuid', 'rel'],
            $data->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('created')
        );
        $this->assertSame(null, $data->get('empty'));
        $this->assertSame($u, $data->get('uuid'));
        $this->assertInstanceOf(CollectionInterface::class, $data->get('rel'));
        $this->assertSame(
            ['created', 'empty', 'child'],
            $data->get('rel')->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('rel')->get('created')
        );
        $this->assertSame(null, $data->get('rel')->get('empty'));
        $this->assertInstanceOf(
            CollectionInterface::class,
            $data->get('rel')->get('child')
        );
        $this->assertSame(
            ['content', 'empty'],
            $data->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame('foo', $data->get('rel')->get('child')->get('content'));
        $this->assertSame(null, $data->get('rel')->get('child')->get('empty'));
    }

    public function testExtractRelationship()
    {
        $entity = new $this->rClass;
        $entity->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->start = new Uuid($s = '11111111-1111-1111-1111-111111111111');
        $entity->end = new Uuid($e = '11111111-1111-1111-1111-111111111111');

        $data = $this->e->extract($entity);

        $this->assertInstanceOf(CollectionInterface::class, $data);
        $this->assertSame(
            ['uuid', 'start', 'end', 'created', 'empty'],
            $data->keys()->toPrimitive()
        );
        $this->assertRegExp(
            '/2016-01-01T00:00:00\+\d{4}/',
            $data->get('created')
        );
        $this->assertSame(null, $data->get('empty'));
        $this->assertSame($u, $data->get('uuid'));
        $this->assertSame($s, $data->get('start'));
        $this->assertSame($e, $data->get('end'));
    }
}
