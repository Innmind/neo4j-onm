<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Hydrator;
use Innmind\Neo4j\ONM\Query;
use Innmind\Neo4j\ONM\IdentityMap;
use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\EntitySilo;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Id;
use Symfony\Component\PropertyAccess\PropertyAccess;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    protected $h;
    protected $r;

    public function setUp()
    {
        $map = new IdentityMap;
        $map->addClass(FooNode::class);
        $map->addClass(FooRel::class);

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
                            ->addOption('rel_type', 'FOO')
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
                    )
                    ->addProperty(
                        (new Property)
                            ->setName('end')
                            ->setType('endNode')
                    )
                    ->setId(
                        (new Id)
                            ->setProperty('id')
                            ->setType('int')
                    )
            );

        $this->h = new Hydrator($map, $this->r, new EntitySilo, PropertyAccess::createPropertyAccessor());
    }

    public function testCreateEntity()
    {
        $m = $this->r->getMetadata(FooNode::class);

        $expected = new FooNode;
        $expected->setId(42);
        $expected->setFoo(new \DateTime('2015-05-31'));

        $this->assertEquals(
            $expected,
            $this->h->createEntity(
                $m,
                [
                    'foo' => '2015-05-31',
                    'id' => '42'
                ]
            )
        );
    }

    public function testCreateEntityOnce()
    {
        $m = $this->r->getMetadata(FooNode::class);

        $expected = $this->h->createEntity(
            $m,
            [
                'foo' => '2015-05-31',
                'id' => '42'
            ]
        );

        $this->assertSame(
            $expected,
            $this->h->createEntity(
                $m,
                [
                    'foo' => '2015-05-31',
                    'id' => '42'
                ]
            )
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
            'Doctrine\Common\Collections\ArrayCollection',
            $result
        );
        $this->assertSame(2, $result->count());
        $node = $result->first();
        $nodeB = $result->last();

        $this->assertSame(
            0,
            $node->getId()
        );
        $this->assertEquals(
            new \DateTime('2015-05-31'),
            $node->getFoo()
        );
        $this->assertSame(
            1,
            $nodeB->getId()
        );
        $this->assertEquals(
            new \DateTime('2015-05-31'),
            $nodeB->getFoo()
        );
        $this->assertInstanceOf(
            FooRel::class,
            $node->getRel()
        );
        $this->assertSame(
            $node->getRel(),
            $nodeB->getRel()
        );
        $this->assertSame(
            $node,
            $node->getRel()->getStart()
        );
        $this->assertSame(
            $nodeB,
            $node->getRel()->getEnd()
        );
        $this->assertSame(
            0,
            $node->getRel()->getId()
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
            'Doctrine\Common\Collections\ArrayCollection',
            $result
        );
        $this->assertSame(1, $result->count());
        $node = $result->first();

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
