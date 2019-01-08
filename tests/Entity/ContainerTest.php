<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity,
    Exception\IdentityNotManaged,
};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testInterface()
    {
        $container = new Container;
        $identity = $this->createMock(Identity::class);

        $this->assertFalse($container->contains($identity));
        $this->assertSame(0, $container->state(State::managed())->size());
        $this->assertSame(0, $container->state(State::new())->size());
        $this->assertSame(0, $container->state(State::toBeRemoved())->size());
        $this->assertSame(0, $container->state(State::removed())->size());
        $this->assertSame(
            $container,
            $container->push($identity, $entity = new \stdClass, State::new())
        );
        $this->assertSame(0, $container->state(State::managed())->size());
        $this->assertSame(1, $container->state(State::new())->size());
        $this->assertSame(0, $container->state(State::toBeRemoved())->size());
        $this->assertSame(0, $container->state(State::removed())->size());
        $this->assertSame(State::new(), $container->stateFor($identity));
        $this->assertSame($entity, $container->get($identity));
        $this->assertTrue($container->contains($identity));
        $this->assertSame($container, $container->detach($identity));
        $this->assertFalse($container->contains($identity));
        $this->assertSame(0, $container->state(State::managed())->size());
        $this->assertSame(0, $container->state(State::new())->size());
        $this->assertSame(0, $container->state(State::toBeRemoved())->size());
        $this->assertSame(0, $container->state(State::removed())->size());
    }

    public function testThrowWhenGettingStateForNotManagedIdentity()
    {
        $this->expectException(IdentityNotManaged::class);

        (new Container)->stateFor($this->createMock(Identity::class));
    }

    public function testThrowWhenGettingEntityForNotManagedEntity()
    {
        $this->expectException(IdentityNotManaged::class);

        (new Container)->get($this->createMock(Identity::class));
    }
}
