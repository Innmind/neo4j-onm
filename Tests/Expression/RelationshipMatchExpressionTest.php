<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\RelationshipMatchExpression;
use Innmind\Neo4j\ONM\Expression\NodeMatchExpression;

class RelationshipMatchExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyRelationship()
    {
        $r = new RelationshipMatchExpression;

        $this->assertSame(
            '-->()',
            (string) $r
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A relationship match can't be specified without the entity alias
     */
    public function testThrowWhenAliasNotSet()
    {
        new RelationshipMatchExpression('r');
    }

    public function testRelationship()
    {
        $r = new RelationshipMatchExpression('r', 'foo');

        $this->assertSame(
            '-[r:foo]->()',
            (string) $r
        );
    }

    public function testRelationshipWithParameters()
    {
        $r = new RelationshipMatchExpression('r', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            '-[r:foo { r_match_props }]->()',
            (string) $r
        );
    }

    public function testRelationshipOnlyOnAlias()
    {
        $r = new RelationshipMatchExpression(null, 'foo');

        $this->assertSame(
            '-[:foo]->()',
            (string) $r
        );
    }

    public function testLeftRelationship()
    {
        $r = new RelationshipMatchExpression;
        $r->setDirection(RelationshipMatchExpression::DIRECTION_LEFT);

        $this->assertSame(
            '<--()',
            (string) $r
        );
    }

    public function testSetDirection()
    {
        $r = new RelationshipMatchExpression;

        $this->assertSame(
            $r,
            $r->setDirection(RelationshipMatchExpression::DIRECTION_LEFT)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowIfInvalidDirection()
    {
        (new RelationshipMatchExpression)->setDirection('foo');
    }

    public function testGetVariable()
    {
        $m = new RelationshipMatchExpression('a', 'foo');

        $this->assertSame(
            'a',
            $m->getVariable()
        );
    }

    public function testDoesntHaveVariable()
    {
        $m = new RelationshipMatchExpression;

        $this->assertFalse($m->hasVariable());
    }

    public function testHasVariable()
    {
        $m = new RelationshipMatchExpression('a', 'foo');

        $this->assertTrue($m->hasVariable());
    }

    public function testGetAlias()
    {
        $m = new RelationshipMatchExpression('a', 'foo');

        $this->assertSame(
            'foo',
            $m->getAlias()
        );
    }

    public function testHasAlias()
    {
        $m = new RelationshipMatchExpression('a', 'foo');

        $this->assertTrue($m->hasAlias());
    }

    public function testHasntAlias()
    {
        $m = new RelationshipMatchExpression;

        $this->assertFalse($m->hasAlias());
    }

    public function testDoesntHaveParameters()
    {
        $m = new RelationshipMatchExpression;

        $this->assertFalse($m->hasParameters());
    }

    public function testHasParameters()
    {
        $m = new RelationshipMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertTrue($m->hasParameters());
    }

    public function testGetParameters()
    {
        $m = new RelationshipMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $m->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $m = new RelationshipMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            'a_match_props',
            $m->getParametersKey()
        );
    }

    public function testHasntReferences()
    {
        $m = new RelationshipMatchExpression;

        $this->assertFalse($m->hasReferences());
    }

    public function testHasReferences()
    {
        $m = new RelationshipMatchExpression('a', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertTrue($m->hasReferences());
    }

    public function testGetReferences()
    {
        $m = new RelationshipMatchExpression('a', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertSame(
            ['foo' => 'string'],
            $m->getReferences()
        );
    }

    public function testGetNodeMatcher()
    {
        $m = new RelationshipMatchExpression;
        $n = new NodeMatchExpression;

        $this->assertSame($m, $m->setNodeMatcher($n));
        $this->assertSame($n, $m->getNodeMatcher());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Parameters to be matched can't be specified without a variable
     */
    public function testThrowIfNoVariableSetWithParams()
    {
        new RelationshipMatchExpression(null, null, ['foo' => 'bar']);
    }

    public function testRelationshipWithoutDirection()
    {
        $r = new RelationshipMatchExpression;
        $r->setDirection(RelationshipMatchExpression::DIRECTION_NONE);

        $this->assertSame(
            '--()',
            (string) $r
        );
    }
}
