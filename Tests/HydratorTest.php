<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Hydrator;
use Innmind\Neo4j\ONM\Query;
use Innmind\Neo4j\ONM\IdentityMap;
use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\ONM\EntitySilo;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Id;
use Innmind\Neo4j\DBAL\ConnectionFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\EventDispatcher\EventDispatcher;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    protected $h;
    protected $r;

    public function setUp()
    {
        $map = new IdentityMap;
        $map->addClass(FooNode::class);
        $map->addClass(FooRel::class);
        $map->addClass('stdClass');

        $this->r = new MetadataRegistry;
        $this->r
            ->addMetadata(
                (new NodeMetadata)
                    ->setClass(FooNode::class)
                    ->addLabel('Foo')
                    ->addProperty(
                        (new Property)
                            ->setName('id')
                            ->setType('int')
                    )
                    ->addProperty(
                        (new Property)
                            ->setName('foo')
                            ->setType('date')
                    )
                    ->addProperty(
                        (new Property)
                            ->setName('rel')
                            ->setType('relationship')
                            ->addOption('relationship', FooRel::class)
                    )
                    ->setId(
                        (new Id)
                            ->setProperty('id')
                            ->setType('int')
                    )
            )
            ->addMetadata(
                (new RelationshipMetadata)
                    ->setClass(FooRel::class)
                    ->setType('FOO')
                    ->addProperty(
                        (new Property)
                            ->setName('id')
                            ->setType('int')
                    )
                    ->addProperty(
                        (new Property)
                            ->setName('start')
                            ->setType('startNode')
                            ->addOption('node', FooNode::class)
                    )
                    ->addProperty(
                        (new Property)
                            ->setName('end')
                            ->setType('endNode')
                            ->addOption('node', FooNode::class)
                    )
                    ->setStartNode('start')
                    ->setEndNode('end')
                    ->setId(
                        (new Id)
                            ->setProperty('id')
                            ->setType('int')
                    )
            );
        $conn = ConnectionFactory::make([
            'host' => getenv('CI') ? 'localhost' : 'docker',
            'username' => 'neo4j',
            'password' => 'ci',
        ]);
        $uow = new UnitOfWork(
            $conn,
            $map,
            $this->r,
            new EventDispatcher
        );

        $this->h = new Hydrator($uow, new EntitySilo, PropertyAccess::createPropertyAccessor());
    }

    public function testCreateEntityOnce()
    {
        $node = [
            'id' => 0,
            'labels' => ['Foo'],
            'properties' => [
                'id' => 0,
                'foo' => '2015-05-31',
            ],
        ];
        $results = [
            'nodes' => [
                0 => $node,
            ],
            'relationships' => [],
            'rows' => [
                'n' => [$node['properties']],
            ],
        ];
        $q = new Query;
        $q->addVariable('n', FooNode::class);

        $result = $this->h->hydrate($results, $q);
        $resultBis = $this->h->hydrate($results, $q);

        $this->assertSame(
            $result->current(),
            $resultBis->current()
        );
    }

    public function testHydrate()
    {
        $nodeA = [
            'id' => 0,
            'labels' => ['Foo'],
            'properties' => [
                'id' => 0,
                'foo' => '2015-05-31',
            ],
        ];
        $nodeB = [
            'id' => 1,
            'labels' => ['Foo'],
            'properties' => [
                'id' => 1,
                'foo' => '2015-05-31',
            ],
        ];
        $rel = [
            'id' => 0,
            'type' => 'FOO',
            'startNode' => 0,
            'endNode' => 1,
            'properties' => [
                'id' => 0,
            ],
        ];
        $results = [
            'nodes' => [
                0 => $nodeA,
                1 => $nodeB,
            ],
            'relationships' => [
                0 => $rel,
            ],
            'rows' => [
                'n' => [$nodeA['properties'], $nodeB['properties']],
                'r' => [$rel['properties']],
            ],
        ];
        $q = new Query;
        $q->addVariable('n', FooNode::class);
        $q->addVariable('r', FooRel::class);

        $result = $this->h->hydrate($results, $q);

        $this->assertInstanceOf(
            'SplObjectStorage',
            $result
        );
        $this->assertSame(3, $result->count());
        $node = $result->current();
        $result->next();
        $result->next();
        $rel = $result->current();

        $this->assertSame(
            0,
            $node->getId()
        );
        $this->assertEquals(
            new \DateTime('2015-05-31'),
            $node->getFoo()
        );
        $this->assertSame(
            0,
            $rel->getId()
        );
        $this->assertInstanceOf(
            FooRel::class,
            $node->getRel()
        );
        $this->assertSame(
            $rel,
            $node->getRel()
        );
        $this->assertSame(
            $node,
            $rel->getStart()
        );
        $result->rewind();
        $result->next();
        $this->assertSame(
            $result->current(),
            $rel->getEnd()
        );
    }

    public function testHydrateWhenNoRelationship()
    {
        $node = [
            'id' => 0,
            'labels' => ['Foo'],
            'properties' => [
                'id' => 0,
                'foo' => '2015-05-31',
            ],
        ];
        $results = [
            'nodes' => [
                0 => $node,
            ],
            'relationships' => [],
            'rows' => [
                'n' => [$node['properties']],
            ],
        ];
        $q = new Query;
        $q->addVariable('n', FooNode::class);

        $result = $this->h->hydrate($results, $q);

        $this->assertInstanceOf(
            'SplObjectStorage',
            $result
        );
        $this->assertSame(1, $result->count());
        $node = $result->current();

        $this->assertSame(
            0,
            $node->getId()
        );
        $this->assertEquals(
            new \DateTime('2015-05-31'),
            $node->getFoo()
        );
    }
}

class FooNode
{
    protected $id;
    protected $foo;
    protected $rel;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setFoo(\DateTime $foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function setRel(FooRel $rel)
    {
        $this->rel = $rel;
    }

    public function getRel()
    {
        return $this->rel;
    }
}

class FooRel
{
    protected $id;
    protected $start;
    protected $end;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStart(FooNode $start)
    {
        $this->start = $start;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function setEnd(FooNode $end)
    {
        $this->end = $end;
    }

    public function getEnd()
    {
        return $this->end;
    }
}
