<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\{
    RepositoryFactory,
    RepositoryInterface,
    Metadata\EntityInterface,
    Metadata\Repository,
    UnitOfWork,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator
};

class RepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $f;

    public function setUp()
    {
        $this->f = new RepositoryFactory(
            $this
                ->getMockBuilder(UnitOfWork::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this
                ->getMockBuilder(MatchTranslator::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this
                ->getMockBuilder(SpecificationTranslator::class)
                ->disableOriginalConstructor()
                ->getMock()
        );
    }

    public function testMake()
    {
        $mock = $this->getMock(RepositoryInterface::class);
        $meta = $this->getMock(EntityInterface::class);
        $meta
            ->method('repository')
            ->willReturn(new Repository(get_class($mock)));
        $repo = $this->f->make($meta);

        $this->assertInstanceOf(get_class($mock), $repo);
        $this->assertSame($repo, $this->f->make($meta));
    }

    public function testRegister()
    {
        $meta = $this->getMock(EntityInterface::class);
        $repo = $this->getMock(RepositoryInterface::class);

        $this->assertSame($this->f, $this->f->register($meta, $repo));
        $this->assertSame($repo, $this->f->make($meta));
    }
}
