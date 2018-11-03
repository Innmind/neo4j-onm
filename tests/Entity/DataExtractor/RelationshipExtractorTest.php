<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity\DataExtractor;

use Innmind\Neo4j\ONM\{
    Entity\DataExtractor\RelationshipExtractor,
    Entity\DataExtractor,
    Metadata\Relationship,
    Metadata\RelationshipEdge,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\Entity,
    Type\DateType,
    Type\StringType,
    Identity\Uuid,
    Type,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class RelationshipExtractorTest extends TestCase
{
    private $extract;
    private $meta;

    public function setUp()
    {
        $this->extract = new RelationshipExtractor;
        $this->meta = Relationship::of(
            new ClassName('foo'),
            new Identity('uuid', 'foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'target'),
            new RelationshipEdge('end', Uuid::class, 'target'),
            Map::of('string', Type::class)
                ('created', new DateType)
                ('empty', StringType::nullable())
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
            public $start;
            public $end;
        };
        $entity->uuid = new Uuid($u = '11111111-1111-1111-1111-111111111111');
        $entity->created = new \DateTimeImmutable('2016-01-01');
        $entity->start = new Uuid($s = '11111111-1111-1111-1111-111111111111');
        $entity->end = new Uuid($e = '11111111-1111-1111-1111-111111111111');

        $extract = new RelationshipExtractor;
        $data = $extract($entity, $this->meta);

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
        ($this->extract)(
            new \stdClass,
            $this->createMock(Entity::class)
        );
    }
}
