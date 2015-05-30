<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\ONM\Query;
use Innmind\Neo4j\ONM\IdentityMap;
use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\Mapping\Id;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected $uow;
    protected $states;
    protected $entities;

    public function setUp()
    {
        $dispatcher = new EventDispatcher;
        $conn = $this
            ->getMockBuilder('Innmind\Neo4j\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $map = new IdentityMap;
        $map->addClass('stdClass');
        $map->addAlias('s', 'stdClass');
        $map->addAlias('b', Bar::class);
        $registry = new MetadataRegistry;
        $registry->addMetadata(
            (new NodeMetadata)
                ->setId(
                    (new Id)
                        ->setStrategy(Id::STRATEGY_AUTO)
                        ->setProperty('id')
                )
                ->addLabel('Foo')
                ->addLabel('Bar')
                ->setClass('stdClass')
        );
        $registry->addMetadata(
            (new RelationshipMetadata)
                ->setId(
                    (new Id)
                        ->setStrategy(Id::STRATEGY_UUID)
                        ->setProperty('id')
                )
                ->setType('foo')
                ->setClass(Bar::class)
        );
        $this->uow = new UnitOfWork(
            $conn,
            $map,
            $registry,
            $dispatcher
        );
        $refl = new \ReflectionObject($this->uow);
        $this->states = $refl->getProperty('states');
        $this->states->setAccessible(true);
        $this->entities = $refl->getProperty('entities');
        $this->entities->setAccessible(true);
    }

    public function testScheduledForInsert()
    {
        $e = new \stdClass;

        $this->assertFalse($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->persist($e));
        $this->assertTrue($this->uow->isManaged($e));
        $this->assertTrue($this->uow->isScheduledForInsert($e));
        $this->assertFalse($this->uow->isScheduledForUpdate($e));
        $this->assertFalse($this->uow->isScheduledForDelete($e));
    }

    public function testScheduledForUpdate()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_MANAGED]->attach($e);
        $entities = $this->entities->getValue($this->uow);
        $entities->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->persist($e));
        $this->assertTrue($this->uow->isManaged($e));
        $this->assertFalse($this->uow->isScheduledForInsert($e));
        $this->assertTrue($this->uow->isScheduledForUpdate($e));
        $this->assertFalse($this->uow->isScheduledForDelete($e));
    }

    public function testScheduledForDelete()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_MANAGED]->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->remove($e));
        $this->assertTrue($this->uow->isManaged($e));
        $this->assertFalse($this->uow->isScheduledForInsert($e));
        $this->assertFalse($this->uow->isScheduledForUpdate($e));
        $this->assertTrue($this->uow->isScheduledForDelete($e));
    }

    public function testNotScheduledForDeleteIfNotInserted()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_NEW]->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->remove($e));
        $this->assertFalse($this->uow->isManaged($e));
        $this->assertFalse($this->uow->isScheduledForInsert($e));
        $this->assertFalse($this->uow->isScheduledForUpdate($e));
        $this->assertFalse($this->uow->isScheduledForDelete($e));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\UnrecognizedEntityException
     */
    public function testThrowWhenPersistingUnknownEntityClass()
    {
        $e = new Foo;

        $this->uow->persist($e);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\UnrecognizedEntityException
     */
    public function testThrowWhenRemovingUnknownEntityClass()
    {
        $e = new Foo;

        $this->uow->remove($e);
    }

    public function testClearAll()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_NEW]->attach($e);
        $entities = $this->entities->getValue($this->uow);
        $entities->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->clear());
        $this->assertFalse($this->uow->isManaged($e));
    }

    public function testClear()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_NEW]->attach($e);
        $entities = $this->entities->getValue($this->uow);
        $entities->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->clear('s'));
        $this->assertFalse($this->uow->isManaged($e));
    }

    public function testDetach()
    {
        $e = new \stdClass;
        $states = $this->states->getValue($this->uow);
        $states[UnitOfWork::STATE_NEW]->attach($e);
        $entities = $this->entities->getValue($this->uow);
        $entities->attach($e);

        $this->assertTrue($this->uow->isManaged($e));
        $this->assertSame($this->uow, $this->uow->detach($e));
        $this->assertFalse($this->uow->isManaged($e));
    }

    public function testBuildQuery()
    {
        $q = new Query('MATCH (n:stdClass)-[r:b]->() WHERE n.id = { where }.nid AND r.id = 42 RETURN n.id;');
        $q->addVariable('n', 'stdClass');
        $q->addVariable('r', 'b');

        $this->assertSame(
            'MATCH (n:Foo:Bar)-[r:FOO]->() WHERE id(n) = { where }.nid AND r.id = 42 RETURN id(n);',
            $this->uow->buildQuery($q)
        );
    }
}

class Foo {}
class Bar {}
