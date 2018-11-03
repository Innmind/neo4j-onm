<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\AggregateExtractor,
    Entity\DataExtractor,
    Metadata\Aggregate,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\ValueObject,
    Metadata\ValueObjectRelationship,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Types,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Set,
};
use Innmind\Reflection\ExtractionStrategy\ReflectionStrategy;
use PHPUnit\Framework\TestCase;

class AggregateExtractorTest extends TestCase
{
    private $extract;
    private $meta;

    public function setUp()
    {
        $this->extract = new AggregateExtractor;
        $this->meta = Aggregate::of(
            new ClassName('foo'),
            new Identity('uuid', 'foo'),
            Set::of('string', 'Label'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )),
            Set::of(
                ValueObject::class,
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
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(DataExtractor::class, $this->extract);
    }

    /**
     * @dataProvider extractionStrategies
     */
    public function testExtract($strategies)
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

        $extract = new AggregateExtractor($strategies);
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

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenExtractingInvalidMeta()
    {
        ($this->extract)(
            new \stdClass,
            $this->createMock(Entity::class)
        );
    }

    public function extractionStrategies(): array
    {
        return [
            [null],
            [new ReflectionStrategy],
        ];
    }
}
