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

    public function testHasntTypes()
    {
        $n = new CreateExpression('n', 'foo', []);

        $this->assertFalse($n->hasTypes());
    }

    public function testHasTypes()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertTrue($n->hasTypes());
    }

    public function testGetTypes()
    {
        $n = new CreateExpression('n', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertSame(
            ['foo' => 'string'],
            $n->getTypes()
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
