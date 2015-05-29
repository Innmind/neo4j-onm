<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\NodeMatchExpression;
use Innmind\Neo4j\ONM\Expression\RelationshipMatchExpression;

class NodeMatchExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyNodeMatcher()
    {
        $m = new NodeMatchExpression;

        $this->assertSame(
            '()',
            (string) $m
        );
    }

    public function testNodeMatch()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertSame(
            '(a:foo)',
            (string) $m
        );
    }

    public function testNodeMatchWithAlias()
    {
        $m = new NodeMatchExpression(null, 'foo');

        $this->assertSame(
            '(:foo)',
            (string) $m
        );
    }

    public function testNodeMatchWithParams()
    {
        $m = new NodeMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            '(a:foo { a_match_props })',
            (string) $m
        );
    }

    public function testNodeMatchOnlyOnAlias()
    {
        $m = new NodeMatchExpression(null, 'foo');

        $this->assertSame(
            '(:foo)',
            (string) $m
        );
    }

    public function testNodeMatchWithRelationship()
    {
        $m = new NodeMatchExpression;
        $m->relatedTo(new RelationshipMatchExpression);

        $this->assertSame(
            '()-->()',
            (string) $m
        );
    }

    public function testNodeMatchWithTypedRelationship()
    {
        $m = new NodeMatchExpression;
        $m->relatedTo(new RelationshipMatchExpression('r', 'foo'));

        $this->assertSame(
            '()-[r:foo]->()',
            (string) $m
        );
    }

    public function testNodeMatchWithTwoRelationships()
    {
        $n1 = new NodeMatchExpression('n1', 'foo');
        $n2 = new NodeMatchExpression('n2', 'bar');
        $n3 = new NodeMatchExpression('n3', 'baz');
        $r1 = new RelationshipMatchExpression('r1', 'foo');
        $r2 = new RelationshipMatchExpression('r2', 'foo');

        $n2->relatedTo($r2, $n3, 'left');
        $n1->relatedTo($r1, $n2);

        $this->assertSame(
            '(n1:foo)-[r1:foo]->(n2:bar)<-[r2:foo]-(n3:baz)',
            (string) $n1
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage A node match can't be specified without the entity alias
     */
    public function testThrowWhenVariableAssignedButNoAlias()
    {
        new NodeMatchExpression('a');
    }

    public function testGetVariable()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertSame(
            'a',
            $m->getVariable()
        );
    }

    public function testDoesntHaveVariable()
    {
        $m = new NodeMatchExpression;

        $this->assertFalse($m->hasVariable());
    }

    public function testHasVariable()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertTrue($m->hasVariable());
    }

    public function testHasAlias()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertTrue($m->hasAlias());
    }

    public function testHasntAlias()
    {
        $m = new NodeMatchExpression;

        $this->assertFalse($m->hasAlias());
    }

    public function testGetAlias()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertSame(
            'foo',
            $m->getAlias()
        );
    }

    public function testDoesntHaveParameters()
    {
        $m = new NodeMatchExpression;

        $this->assertFalse($m->hasParameters());
    }

    public function testHasParameters()
    {
        $m = new NodeMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertTrue($m->hasParameters());
    }

    public function testGetParameters()
    {
        $m = new NodeMatchExpression('a', 'foo', ['foo' => 'bar']);

        $this->assertSame(
            ['foo' => 'bar'],
            $m->getParameters()
        );
    }

    public function testGetParametersKey()
    {
        $m = new NodeMatchExpression('a', 'foo');

        $this->assertSame(
            'a_match_props',
            $m->getParametersKey()
        );
    }

    public function testHasntTypes()
    {
        $m = new NodeMatchExpression;

        $this->assertFalse($m->hasTypes());
    }

    public function testHasTypes()
    {
        $m = new NodeMatchExpression('a', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertTrue($m->hasTypes());
    }

    public function testGetTypes()
    {
        $m = new NodeMatchExpression('a', 'foo', ['foo' => 'bar'], ['foo' => 'string']);

        $this->assertSame(
            ['foo' => 'string'],
            $m->getTypes()
        );
    }

    public function testSetRelationship()
    {
        $m = new NodeMatchExpression;
        $r = new RelationshipMatchExpression;

        $this->assertFalse($m->hasRelationship());
        $this->assertSame($m, $m->relatedTo($r));
        $this->assertTrue($m->hasRelationship());
        $this->assertSame(
            $r,
            $m->getRelationship()
        );
    }

    public function testDoesnOverrideRelationshipNodeMatcher()
    {
        $m = new NodeMatchExpression;
        $m2 = new NodeMatchExpression;
        $r = new RelationshipMatchExpression;

        $r->setNodeMatcher($m2);
        $m->relatedTo($r);

        $this->assertSame(
            $m2,
            $r->getNodeMatcher()
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Parameters to be matched can't be specified without a variable
     */
    public function testThrowIfNoVariableSetWithParams()
    {
        new NodeMatchExpression(null, null, ['foo' => 'bar']);
    }
}
