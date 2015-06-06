<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\RepositoryFactory;
use Innmind\Neo4j\ONM\IdentityMap;
use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;

class RepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;
    protected $map;
    protected $metadata;
    protected $em;

    public function setUp()
    {
        $this->map = new IdentityMap;
        $this->metadata = new MetadataRegistry;
        $this->factory = new RepositoryFactory(
            $this->map,
            $this->metadata
        );
        $this->map->addClass('stdClass');
        $this->metadata->addMetadata(
            (new NodeMetadata)
                ->setClass('stdClass')
                ->addLabel('foo')
        );
        $this->em = $this->getMockBuilder('Innmind\Neo4j\ONM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testMakeDefaultRepository()
    {
        $this->assertInstanceOf(
            'Innmind\Neo4j\ONM\Repository',
            $this->factory->make('stdClass', $this->em)
        );
    }

    public function testRepositoryBuildOnlyOnce()
    {
        $repo = $this->factory->make('stdClass', $this->em);

        $this->assertSame(
            $repo,
            $this->factory->make('stdClass', $this->em)
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\RepositoryException
     * @expectedExceptionMessage The repository "stdClass" must implement "Innmind\Neo4j\ONM\RepositoryInterface"
     * @expectedExceptionCode 1
     */
    public function testThrowWhenInvalidRepositoryInstance()
    {
        $this
            ->metadata
            ->addMetadata(
                (new NodeMetadata)
                    ->setClass('stdClass')
                    ->setRepositoryClass('stdClass')
                    ->addLabel('foo')
            );
        $this->map->addClass('stdClass');

        $this->factory->make('stdClass', $this->em);
    }
}
