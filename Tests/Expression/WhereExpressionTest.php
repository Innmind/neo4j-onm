<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\WhereExpression;

class WhereExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Where expression parameters can't be used without a key name
     */
    public function testThrowIfParametersWithoutAKey()
    {
        new WhereExpression('foo', null, ['foo' => 'bar']);
    }

    public function testHasntParameters()
    {
        $w = new WhereExpression('foo');

        $this->assertFalse($w->hasParameters());
    }

    public function testHasParameters()
    {
        $w = new WhereExpression('foo', 'foo', ['foo' => 'bar']);

        $this->assertTrue($w->hasParameters());
    }

    public function testGetParameters()
    {
        $w = new WhereExpression('foo', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $w->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $w = new WhereExpression('foo', 'where');

        $this->assertSame(
            'where',
            $w->getParametersKey()
        );
    }

    public function testHasntReferences()
    {
        $w = new WhereExpression('foo');

        $this->assertFalse($w->hasReferences());
    }

    public function testHasReferences()
    {
        $w = new WhereExpression('foo', null, null, ['foo' => 'string']);

        $this->assertTrue($w->hasReferences());
    }

    public function testGetReferences()
    {
        $w = new WhereExpression('foo', null, null, ['foo' => 'string']);

        $this->assertSame(
            ['foo' => 'string'],
            $w->getReferences()
        );
    }

    public function testStringRepresentation()
    {
        $w = new WhereExpression('foo');

        $this->assertSame(
            'foo',
            (string) $w
        );
    }
}