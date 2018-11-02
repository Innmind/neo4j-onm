<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory,
    EntityFactory\Resolver,
    Metadata\Entity,
    Metadata\Factory,
};
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    public function testInterface()
    {
        $resolve = new Resolver;

        $class = $this->getMockClass(EntityFactory::class);
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));

        $this->assertInstanceOf(
            EntityFactory::class,
            $resolve($meta)
        );
        $this->assertInstanceOf($class, $resolve($meta));
        $this->assertSame($resolve($meta), $resolve($meta));
    }

    public function testRegister()
    {
        $class = $this->getMockClass(EntityFactory::class);
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));
        $factory = new $class;

        $resolve = new Resolver($factory);

        $this->assertSame($factory, $resolve($meta));
    }
}
