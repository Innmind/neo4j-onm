<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $qb;

    public function setUp()
    {
        $this->qb = new QueryBuilder;
    }

    public function testGetExpressionBuilder()
    {
        $this->assertInstanceOf(
            'Innmind\Neo4j\ONM\Expression\Builder',
            $this->qb->expr()
        );
    }

    public function testMatchNode()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->matchNode('n', 'Foo', ['foo' => 'bar'])
        );
        $this->assertSame(
            'MATCH (n:Foo {foo: {n_match_props}.foo});',
            (string) $this->qb->getQuery()
        );
        $this->assertSame(
            ['n_match_props' => ['foo' => 'bar']],
            $this->qb->getQuery()->getParameters()
        );
        $this->assertSame(
            ['n_match_props' => ['foo' => 'n.foo']],
            $this->qb->getQuery()->getReferences()
        );
        $this->assertSame(
            ['n' => 'Foo'],
            $this->qb->getQuery()->getVariables()
        );
    }

    public function testAddExpr()
    {
        $rel = $this->qb->expr()->matchRelationship('r', 'FOO');
        $n = $this->qb->expr()->matchNode('n', 'Foo');
        $n->relatedTo($rel);

        $this->assertSame(
            $this->qb,
            $this->qb->addExpr($n)
        );
        $this->assertSame(
            'MATCH (n:Foo)-[r:FOO]->();',
            (string) $this->qb->getQuery()
        );
    }

    public function testUpdate()
    {
        $this->qb->matchNode('n', 'Foo');
        $this->assertSame(
            $this->qb,
            $this->qb->update('n', ['foo' => 'bar'])
        );
        $this->assertSame(
            'MATCH (n:Foo)' . "\n" . 'SET n += { n_update_props };',
            (string) $this->qb->getQuery()
        );
        $this->assertSame(
            ['n_update_props' => ['foo' => 'bar']],
            $this->qb->getQuery()->getParameters()
        );
        $this->assertSame(
            ['n_update_props' => ['foo' => 'n.foo']],
            $this->qb->getQuery()->getReferences()
        );
    }

    public function testCreate()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->create('n', 'Foo', ['foo' => 'bar'])
        );
        $this->assertSame(
            'CREATE (n:Foo { n_create_props });',
            (string) $this->qb->getQuery()
        );
        $this->assertSame(
            ['n_create_props' => ['foo' => 'bar']],
            $this->qb->getQuery()->getParameters()
        );
        $this->assertSame(
            ['n_create_props' => ['foo' => 'n.foo']],
            $this->qb->getQuery()->getReferences()
        );
        $this->assertSame(
            ['n' => 'Foo'],
            $this->qb->getQuery()->getVariables()
        );
    }

    public function testRemove()
    {
        $this->qb->matchNode('n', 'Foo');
        $this->assertSame(
            $this->qb,
            $this->qb->remove('n')
        );
        $this->assertSame(
            'MATCH (n:Foo)' . "\n" . 'REMOVE n;',
            (string) $this->qb->getQuery()
        );
    }

    public function testWhere()
    {
        $this->qb->matchNode('n', 'Foo');
        $this->assertSame(
            $this->qb,
            $this->qb->where('n.id = { where }.nid', 'where', ['nid' => 42], ['nid' => 'int'])
        );
        $this->assertSame(
            'MATCH (n:Foo)' . "\n" . 'WHERE n.id = { where }.nid;',
            (string) $this->qb->getQuery()
        );
        $this->assertSame(
            ['where' => ['nid' => 42]],
            $this->qb->getQuery()->getParameters()
        );
        $this->assertSame(
            ['where' => ['nid' => 'int']],
            $this->qb->getQuery()->getReferences()
        );
    }

    public function testToReturn()
    {
        $this->qb->matchNode('n', 'Foo');
        $this->assertSame(
            $this->qb,
            $this->qb->toReturn('n')
        );
        $this->assertSame(
            'MATCH (n:Foo)' . "\n" . 'RETURN n;',
            (string) $this->qb->getQuery()
        );
    }

    public function testOrderBy()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->orderBy('foo', 'DESC')
        );
        $this->assertSame(
            'ORDER BY foo DESC;',
            (string) $this->qb->getQuery()
        );
    }

    public function testOrderByASCByDefault()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->orderBy('foo', 'foo')
        );
        $this->assertSame(
            'ORDER BY foo ASC;',
            (string) $this->qb->getQuery()
        );
    }

    public function testSkip()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->skip(42)
        );
        $this->assertSame(
            'SKIP 42;',
            (string) $this->qb->getQuery()
        );
    }

    public function testLimit()
    {
        $this->assertSame(
            $this->qb,
            $this->qb->limit(42)
        );
        $this->assertSame(
            'LIMIT 42;',
            (string) $this->qb->getQuery()
        );
    }
}
