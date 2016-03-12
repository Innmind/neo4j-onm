<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Entity\DataExtractor;

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
    Identity\Uuid
};
use Innmind\Immutable\{
    CollectionInterface,
    Collection
};

class RelationshipExtractorTest extends \PHPUnit_Framework_TestCase
{
    private $e;
    private $m;

    public function setUp()
    {
        $this->e = new RelationshipExtractor;
        $this->m = new Relationship(
            new ClassName('foo'),
            new Identity('uuid', 'foo'),
            new Repository('foo'),
            new Factory('foo'),
            new Alias('foo'),
            new RelationshipType('type'),
            new RelationshipEdge('start', Uuid::class, 'target'),
            new RelationshipEdge('end', Uuid::class, 'target')
        );
        $this->m = $this->m
            ->withProperty('created', new DateType)
            ->withProperty(
                'empty',
                StringType::fromConfig(
                    new Collection(['nullable' => null])
                )
            );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(DataExtractorInterface::class, $this->e);
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

        $data = $this->e->extract($entity, $this->m);

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

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenExtractingInvalidMeta()
    {
        $this->e->extract(
            new \stdClass,
            $this->getMock(EntityInterface::class)
        );
    }
}
