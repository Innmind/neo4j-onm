<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\UnitOfWork;
use Innmind\Neo4j\ONM\Query;
use Innmind\Neo4j\ONM\IdentityMap;
use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\Mapping\Id;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\DBAL\ConnectionFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected $uow;
    protected $states;
    protected $entities;

    public function setUp()
    {
        $dispatcher = new EventDispatcher;
        $conn = ConnectionFactory::make([
            'host' => getenv('CI') ? 'localhost' : 'docker',
            'username' => 'neo4j',
            'password' => 'ci',
        ]);
        $map = new IdentityMap;
        $map->addClass('stdClass');
        $map->addClass(Baz::class);
        $map->addAlias('s', 'stdClass');
        $map->addAlias('b', Bar::class);
        $map->addAlias('f', Baz::class);
        $registry = new MetadataRegistry;
        $registry->addMetadata(
            (new NodeMetadata)
                ->setId(
                    (new Id)
                        ->setStrategy('UUID')
                        ->setProperty('id')
                        ->setType('int')
                )
                ->addLabel('Foo')
                ->addLabel('Bar')
                ->setClass('stdClass')
                ->addProperty(
                    (new Property)
                        ->setName('created')
                        ->setType('date')
                )
        );
        $registry->addMetadata(
            (new NodeMetadata)
                ->setId(
                    (new Id)
                        ->setStrategy('UUID')
                        ->setProperty('id')
                        ->setType('int')
                )
                ->addLabel('Foo')
                ->addLabel('Bar')
                ->setClass(Baz::class)
                ->addProperty(
                    (new Property)
                        ->setName('id')
                        ->setType('string')
                )
                ->addProperty(
                    (new Property)
                        ->setName('name')
                        ->setType('string')
                )
                ->addProperty(
                    (new Property)
                        ->setName('rel')
                        ->setType('relationship')
                        ->addOption('relationship', Bar::class)
                )
        );
        $registry->addMetadata(
            (new RelationshipMetadata)
                ->setId(
                    (new Id)
                        ->setStrategy('UUID')
                        ->setProperty('id')
                )
                ->setType('FOO')
                ->setClass(Bar::class)
                ->addProperty(
                    (new Property)
                        ->setName('id')
                        ->setType('string')
                )
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
        $e = new Baz;

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
            'MATCH (n:Foo:Bar)-[r:FOO]->() WHERE n.id = { where }.nid AND r.id = 42 RETURN n.id;',
            $this->uow->buildQuery($q)
        );
    }

    public function testExecute()
    {
        $q = new Query('CREATE (n:f { props }) RETURN n;');
        $q->addVariable('n', 'f');
        $q->addParameters('props', ['id' => 'some-uuid', 'name' => 'myself']);

        $result = $this->uow->execute($q);

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $result
        );
        $this->assertSame(
            1,
            $result->count()
        );
        $this->assertInstanceOf(
            Baz::class,
            $result->first()
        );
        $this->assertSame(
            'some-uuid',
            $result->first()->id
        );
        $this->assertSame(
            'myself',
            $result->first()->name
        );
    }

    public function testFind()
    {
        $q = new Query('CREATE (n:f {props}) RETURN n;');
        $q->addVariable('n', 'f');
        $q->addParameters('props', ['id' => 'foo-bar-baz', 'name' => 'me']);
        $this->uow->execute($q);

        $entity = $this->uow->find('f', 'foo-bar-baz');

        $this->assertInstanceOf(
            Baz::class,
            $entity
        );
        $this->assertSame(
            'foo-bar-baz',
            $entity->id
        );
        $this->assertSame(
            'me',
            $entity->name
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\EntityNotFoundException
     */
    public function testThrowWhenEntityNotFound()
    {
        $this->uow->find('f', 'unknown');
    }

    public function testFindBy()
    {
        $q = new Query('CREATE (n:f {prop})-[r:b]->(b);');
        $q->addVariable('n', 'f');
        $q->addVariable('r', 'b');
        $q->addParameters('prop', ['id' => 'random', 'name' => 'me']);
        $this->uow->execute($q);

        $result = $this->uow->findBy('f', ['name' => 'me'], ['id', 'ASC'], 1, 1);

        $this->assertSame(
            1,
            $result->count()
        );
        $this->assertInstanceOf(
            Baz::class,
            $result->first()
        );
    }

    public function testPersist()
    {
        $n = new Baz;

        $this->assertFalse($this->uow->isManaged($n));
        $this->assertSame(
            $this->uow,
            $this->uow->persist($n)
        );
        $this->assertTrue($this->uow->isManaged($n));
        $this->assertTrue($this->uow->isScheduledForInsert($n));
        $this->uow->persist($n);
        $this->assertTrue($this->uow->isScheduledForInsert($n));
        $this->assertFalse($this->uow->isScheduledForUpdate($n));
    }
}

class Foo {}
class Baz {
    public $id;
    public $name;
    public $rel;
}
class Bar {}
