<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\CreateExpression;

class CreateExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRepresentation()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            '(n:foo { n_create_props })',
            (string) $n
        );
    }

    public function testHasParameters()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar']);

        $this->assertTrue($n->hasParameters());
    }

    public function testGetParameters()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $n->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertSame(
            'n_create_props',
            $n->getParametersKey()
        );
    }

    public function testHasntReferences()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertFalse($n->hasReferences());
    }

    public function testHasReferences()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar']);

        $this->assertTrue($n->hasReferences());
    }

    public function testGetReferences()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'n.foo'],
            $n->getReferences()
        );
    }

    public function testHasVariable()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertTrue($n->hasVariable());
    }

    public function testGetVariable()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertSame(
            'n',
            $n->getVariable()
        );
    }

    public function testHasAlias()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertTrue($n->hasAlias());
    }

    public function testGetAlias()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertSame(
            'foo',
            $n->getAlias()
        );
    }
}
