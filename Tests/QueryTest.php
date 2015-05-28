<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testSetQuery()
    {
        $q = new Query('MATCH (n)');

        $this->assertSame(
            'MATCH (n)',
            $q->getCypher()
        );
        $this->assertSame(
            'MATCH (n)',
            (string) $q
        );

        $q = new Query;

        $this->assertSame(
            $q,
            $q->setCypher('MATCH (n2)')
        );
        $this->assertSame(
            'MATCH (n2)',
            (string) $q
        );
    }

    public function testSetParameters()
    {
        $q = new Query;

        $this->assertFalse($q->hasParameters());
        $this->assertSame(
            $q,
            $q->addParameters('foo', 'bar')
        );
        $this->assertTrue($q->hasParameters());
        $this->assertSame(
            ['foo' => 'bar'],
            $q->getParameters()
        );
    }

    public function testSetTypes()
    {
        $q = new Query;

        $this->assertFalse($q->hasTypes());
        $q->addParameters('foo', 'bar', 'string');
        $this->assertTrue($q->hasTypes());
        $this->assertSame(
            ['foo' => 'string'],
            $q->getTypes()
        );
    }

    public function testSetVariable()
    {
        $q = new Query;

        $this->assertFalse($q->hasVariables());
        $this->assertSame(
            $q,
            $q->addVariable('n', 'Entity\Class')
        );
        $this->assertTrue($q->hasVariables());
        $this->assertSame(
            ['n' => 'Entity\Class'],
            $q->getVariables()
        );
    }
}
