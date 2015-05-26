<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuery()
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
    }

    public function testHasParameters()
    {
        $q = new Query('');
        $this->assertFalse($q->hasParameters());

        $q = new Query('', []);
        $this->assertFalse($q->hasParameters());

        $q = new Query('', ['foo' => 'bar']);
        $this->assertTrue($q->hasParameters());
    }

    public function testGetParameters()
    {
        $q = new Query('', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $q->getParameters()
        );
    }
}
