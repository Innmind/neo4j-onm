<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\CypherExpression;

class CypherExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Cypher query parameters can't be used without a key name
     */
    public function testThrowIfParametersWithoutAKey()
    {
        new CypherExpression('foo', null, ['foo' => 'bar']);
    }
}
