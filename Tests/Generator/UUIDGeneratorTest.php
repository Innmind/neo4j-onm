<?php

namespace Innmind\Neo4j\ONM\Tests\Generator;

use Innmind\Neo4j\ONM\Generator\UUIDGenerator;

class UUIDGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $g;
    protected $m;

    public function setUp()
    {
        $this->m = $this
            ->getMockBuilder('Innmind\Neo4j\ONM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->g = new UUIDGenerator;
    }

    public function testGenerate()
    {
        $id = $this->g->generate($this->m, new \stdClass);

        $this->assertSame(
            1,
            preg_match(
                '/^[a-z0-9]{8}-([a-z0-9]{4}-){3}[a-z0-9]{12}$/',
                $id
            )
        );
    }

    public function testGetStrategy()
    {
        $this->assertSame(
            'UUID',
            $this->g->getStrategy()
        );
    }
}
