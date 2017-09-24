<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\Container,
    Entity\Container\State,
    Identity
};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testInterface()
    {
        $c = new Container;
        $i = $this->createMock(Identity::class);

        $this->assertFalse($c->contains($i));
        $this->assertSame(0, $c->state(State::managed())->size());
        $this->assertSame(0, $c->state(State::new())->size());
        $this->assertSame(0, $c->state(State::toBeRemoved())->size());
        $this->assertSame(0, $c->state(State::removed())->size());
        $this->assertSame(
            $c,
            $c->push($i, $e = new \stdClass, State::new())
        );
        $this->assertSame(0, $c->state(State::managed())->size());
        $this->assertSame(1, $c->state(State::new())->size());
        $this->assertSame(0, $c->state(State::toBeRemoved())->size());
        $this->assertSame(0, $c->state(State::removed())->size());
        $this->assertSame(State::new(), $c->stateFor($i));
        $this->assertSame($e, $c->get($i));
        $this->assertTrue($c->contains($i));
        $this->assertSame($c, $c->detach($i));
        $this->assertFalse($c->contains($i));
        $this->assertSame(0, $c->state(State::managed())->size());
        $this->assertSame(0, $c->state(State::new())->size());
        $this->assertSame(0, $c->state(State::toBeRemoved())->size());
        $this->assertSame(0, $c->state(State::removed())->size());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\IdentityNotManaged
     */
    public function testThrowWhenGettingStateForNotManagedIdentity()
    {
        (new Container)->stateFor($this->createMock(Identity::class));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\IdentityNotManaged
     */
    public function testThrowWhenGettingEntityForNotManagedEntity()
    {
        (new Container)->get($this->createMock(Identity::class));
    }
}
