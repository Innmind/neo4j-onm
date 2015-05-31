<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\EntitySilo;

class EntitySiloTest extends \PHPUnit_Framework_TestCase
{
    protected $s;

    public function setUp()
    {
        $this->s = new EntitySilo;
    }

    public function testAdd()
    {
        $n = new \stdClass;

        $this->assertFalse($this->s->has(get_class($n), 1));
        $this->assertSame(
            $this->s,
            $this->s->add($n, get_class($n), 1)
        );
        $this->assertTrue($this->s->has(get_class($n), 1));
        $this->assertSame(
            $n,
            $this->s->get(get_class($n), 1)
        );
    }

    public function testContains()
    {
        $n = new \stdClass;
        $this->s->add($n, get_class($n), 1);

        $this->assertTrue($this->s->contains($n));
    }
}
