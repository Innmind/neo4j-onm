<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Query;

use Innmind\Neo4j\ONM\Query\Where;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class WhereTest extends TestCase
{
    public function testInterface()
    {
        $where = new Where(
            'foo',
            $parameters = new Map('string', 'mixed')
        );

        $this->assertSame('foo', $where->cypher());
        $this->assertSame($parameters, $where->parameters());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyCypher()
    {
        new Where('', new Map('string', 'mixed'));
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenInvalidParameterMap()
    {
        new Where('foo', new Map('string', 'variable'));
    }

    public function testAnd()
    {
        $where = new Where(
            'foo',
            (new Map('string', 'mixed'))
                ->put('foo', 'bar')
        );

        $where2 = $where->and(
            new Where(
                'bar',
                (new Map('string', 'mixed'))
                    ->put('bar', 'baz')
            )
        );

        $this->assertInstanceOf(Where::class, $where2);
        $this->assertNotSame($where, $where2);
        $this->assertSame('foo', $where->cypher());
        $this->assertCount(1, $where->parameters());
        $this->assertSame('(foo AND bar)', $where2->cypher());
        $this->assertCount(2, $where2->parameters());
        $this->assertSame('bar', $where2->parameters()->get('foo'));
        $this->assertSame('baz', $where2->parameters()->get('bar'));
    }

    public function testOr()
    {
        $where = new Where(
            'foo',
            (new Map('string', 'mixed'))
                ->put('foo', 'bar')
        );

        $where2 = $where->or(
            new Where(
                'bar',
                (new Map('string', 'mixed'))
                    ->put('bar', 'baz')
            )
        );

        $this->assertInstanceOf(Where::class, $where2);
        $this->assertNotSame($where, $where2);
        $this->assertSame('foo', $where->cypher());
        $this->assertCount(1, $where->parameters());
        $this->assertSame('(foo OR bar)', $where2->cypher());
        $this->assertCount(2, $where2->parameters());
        $this->assertSame('bar', $where2->parameters()->get('foo'));
        $this->assertSame('baz', $where2->parameters()->get('bar'));
    }

    public function testNot()
    {

        $where = new Where(
            'foo',
            (new Map('string', 'mixed'))
                ->put('foo', 'bar')
        );

        $where2 = $where->not();

        $this->assertInstanceOf(Where::class, $where2);
        $this->assertNotSame($where, $where2);
        $this->assertSame('foo', $where->cypher());
        $this->assertSame('NOT (foo)', $where2->cypher());
        $this->assertSame($where->parameters(), $where2->parameters());
    }
}
