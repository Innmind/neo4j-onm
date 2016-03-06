<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactoryInterface,
    EntityFactory\Resolver,
    Metadata\EntityInterface,
    Metadata\Factory
};

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $r = new Resolver;

        $class = $this->getMockClass(EntityFactoryInterface::class);
        $meta = $this->getMock(EntityInterface::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));

        $this->assertInstanceOf(
            EntityFactoryInterface::class,
            $r->get($meta)
        );
        $this->assertInstanceOf($class, $r->get($meta));
        $this->assertSame($r->get($meta), $r->get($meta));
    }

    public function testAdd()
    {
        $r = new Resolver;

        $class = $this->getMockClass(EntityFactoryInterface::class);
        $meta = $this->getMock(EntityInterface::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));
        $f = new $class;

        $this->assertSame($r, $r->add($f));
        $this->assertSame($f, $r->get($meta));
    }
}
