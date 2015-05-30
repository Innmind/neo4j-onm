<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\UpdateExpression;

class UpdateExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdateRepresentation()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            'n += { n_update_props }',
            (string) $u
        );
    }

    public function testHasParameters()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertTrue($u->hasParameters());
    }

    public function testGetParameters()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $u->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            'n_update_props',
            $u->getParametersKey()
        );
    }

    public function testHasntReferences()
    {
        $u = new UpdateExpression('n', []);

        $this->assertFalse($u->hasReferences());
    }

    public function testHasReferences()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertTrue($u->hasReferences());
    }

    public function testGetReferences()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'n.foo'],
            $u->getReferences()
        );
    }

    public function testHasVariable()
    {
        $u = new UpdateExpression('n', []);

        $this->assertTrue($u->hasVariable());
    }

    public function testGetVariable()
    {
        $u = new UpdateExpression('n', []);

        $this->assertSame(
            'n',
            $u->getVariable()
        );
    }
}
