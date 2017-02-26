<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\RelationshipExtractor,
    Entity\DataExtractorInterface,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\Repository,
    Metadata\Factory,
    Metadata\Alias,
    Metadata\RelationshipType,
    Metadata\EntityInterface,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Types
};
use Innmind\Immutable\{
    MapInterface,
    Map
};
use Innmind\Reflection\ExtractionStrategy\ReflectionStrategy;
use PHPUnit\Framework\TestCase;

class RelationshipExtractorTest extends TestCase
{
    private $extractor;
    private $meta;

    public function setUp()
    {
        $this->extractor = new RelationshipExtractor;
        $this->meta = new Relationship(
            new ClassName('foo'),
            new Identity('uuid', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'target'),
            new RelationshipEdge('end', Uuid::class, 'target')
        );
        $this->meta = $this->meta
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    (new Map('string', 'mixed'))
                        ->put('nullable', null),
                    new Types
                )
            );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(DataExtractorInterface::class, $this->extractor);
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
            public $start;
            public $end;
        };
        $entity->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->start = new Uuid($s = '11111111-1111-1111-1111-111111111111');
        $entity->end = new Uuid($e = '11111111-1111-1111-1111-111111111111');

        $extractor = new RelationshipExtractor($strategies);
        $data = $extractor->extract($entity, $this->meta);

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
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenExtractingInvalidMeta()
    {
        $this->extractor->extract(
            new \stdClass,
            $this->createMock(EntityInterface::class)
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
