<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\EntityFactory;

use Innmind\Neo4j\ONM\{
    EntityFactory,
    EntityFactory\Resolver,
    Metadata\Entity,
    Metadata\Factory
};
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    public function testInterface()
    {
        $resolver = new Resolver;

        $class = $this->getMockClass(EntityFactory::class);
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));

        $this->assertInstanceOf(
            EntityFactory::class,
            $resolver->get($meta)
        );
        $this->assertInstanceOf($class, $resolver->get($meta));
        $this->assertSame($resolver->get($meta), $resolver->get($meta));
    }

    public function testRegister()
    {
        $class = $this->getMockClass(EntityFactory::class);
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('factory')
            ->willReturn(new Factory($class));
        $factory = new $class;

        $resolver = new Resolver($factory);

        $this->assertSame($factory, $resolver->get($meta));
    }
}
