<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\AggregateExtractor,
    Entity\DataExtractor,
    Metadata\Aggregate,
    Metadata\Aggregate\Child,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Type,
    Exception\InvalidArgumentException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class AggregateExtractorTest extends TestCase
{
    private $extract;
    private $meta;

    public function setUp(): void
    {
        $this->extract = new AggregateExtractor;
        $this->meta = Aggregate::of(
            new ClassName('foo'),
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
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(DataExtractor::class, $this->extract);
    }

    public function testExtract()
    {
        $entity = new class {
            public $uuid;
            public $created;
            public $empty;
            public $rel;
        };
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

        $extract = new AggregateExtractor;
        $data = $extract($entity, $this->meta);

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

    public function testThrowWhenExtractingInvalidMeta()
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->extract)(
            new \stdClass,
            $this->createMock(Entity::class)
        );
    }
}
