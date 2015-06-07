<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\CreateRelationshipExpression;

class CreateRelationshipExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRepresentation()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            '(s)-[r:foo { r_create_props }]->(e)',
            (string) $n
        );
    }

    public function testHasParameters()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', ['foo' => 'bar']);

        $this->assertTrue($n->hasParameters());
    }

    public function testGetParameters()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $n->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', []);

        $this->assertSame(
            'r_create_props',
            $n->getParametersKey()
        );
    }

    public function testHasReferences()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', ['foo' => 'bar']);

        $this->assertTrue($n->hasReferences());
    }

    public function testGetReferences()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'r.foo'],
            $n->getReferences()
        );
    }

    public function testHasVariable()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', []);

        $this->assertTrue($n->hasVariable());
    }

    public function testGetVariable()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', []);

        $this->assertSame(
            'r',
            $n->getVariable()
        );
    }

    public function testHasAlias()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', []);

        $this->assertTrue($n->hasAlias());
    }

    public function testGetAlias()
    {
        $n = new CreateRelationshipExpression('s', 'e', 'r', 'foo', []);

        $this->assertSame(
            'foo',
            $n->getAlias()
        );
    }
}
